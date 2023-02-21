# syntax=docker/dockerfile:1
FROM mcr.microsoft.com/mssql/server:2022-latest
USER root

RUN export DEBIAN_FRONTEND=noninteractive && \
apt-get update --fix-missing && \
apt-get install -y gnupg2 && \
apt-get install -yq curl apt-transport-https && \
curl https://packages.microsoft.com/keys/microsoft.asc | tac | tac | apt-key add - && \
curl https://packages.microsoft.com/config/ubuntu/20.04/mssql-server-2019.list | tac | tac | tee /etc/apt/sources.list.d/mssql-server.list && \
#curl https://packages.microsoft.com/config/ubuntu/22.04/mssql-server-2022.list | tac | tac | tee /etc/apt/sources.list.d/mssql-server.list && \
apt-get update

RUN apt-get install -y mssql-server-fts

# Run SQL Server process
CMD /opt/mssql/bin/sqlservr


FROM php:7.4-apache AS composer-builder

# Install Zip to use composer
RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    libzip-dev \
    unzip
RUN docker-php-ext-install zip

# Install and update composer
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN composer self-update

USER www-data
WORKDIR /composer
COPY composer.* /composer/
RUN composer install

# Site
FROM php:7.4-apache
COPY resources/keyman-site.conf /etc/apache2/conf-available/
RUN chown -R www-data:www-data /var/www/html/

# Install SQL drivers
# https://learn.microsoft.com/en-us/sql/connect/php/installation-tutorial-linux-mac?view=sql-server-ver16
# https://stackoverflow.com/a/72176870
RUN apt-get update && apt-get install -y gnupg2

# Adding custom MS repo
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - 
RUN curl https://packages.microsoft.com/config/ubuntu/22.04/prod.list > /etc/apt/sources.list.d/mssql-release.list

## Install SQL Server drivers
RUN apt-get update && ACCEPT_EULA=Y apt-get install -y msodbcsql18 unixodbc-dev

RUN pecl install sqlsrv pdo_sqlsrv
RUN docker-php-ext-install pdo pdo_mysql
RUN docker-php-ext-enable sqlsrv pdo_sqlsrv pdo pdo_mysql
COPY --from=composer-builder /composer/vendor /var/www/vendor
# RUN ls -l /var/www/ &&  php /var/www/html/tools/db/build/build_cli.php
RUN a2enmod rewrite; a2enconf keyman-site \
#    service apache2 restart
