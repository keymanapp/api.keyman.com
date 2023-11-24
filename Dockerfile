# syntax=docker/dockerfile:1
FROM php:7.4-apache@sha256:c9d7e608f73832673479770d66aacc8100011ec751d1905ff63fae3fe2e0ca6d AS composer-builder

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
FROM php:7.4-apache@sha256:c9d7e608f73832673479770d66aacc8100011ec751d1905ff63fae3fe2e0ca6d
COPY resources/keyman-site.conf /etc/apache2/conf-available/
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
RUN echo memory_limit = 1024M >> /usr/local/etc/php/php.ini
RUN chown -R www-data:www-data /var/www/html/

# Install SQL drivers
# https://learn.microsoft.com/en-us/sql/connect/php/installation-tutorial-linux-mac?view=sql-server-ver16
# https://stackoverflow.com/a/72176870
RUN apt-get update && apt-get install -y gnupg2

# Adding custom MS repo
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
RUN curl https://packages.microsoft.com/config/ubuntu/22.04/prod.list > /etc/apt/sources.list.d/mssql-release.list

## Install SQL Server drivers and Zip
RUN apt-get update && ACCEPT_EULA=Y apt-get -y --no-install-recommends install msodbcsql18 unixodbc-dev zip libzip-dev
RUN pecl install sqlsrv-5.10.1
RUN pecl install pdo_sqlsrv-5.10.1
RUN docker-php-ext-install pdo pdo_mysql zip
RUN docker-php-ext-enable sqlsrv pdo_sqlsrv pdo pdo_mysql
COPY --from=composer-builder /composer/vendor /var/www/vendor

# This is handled in init-container.sh
# RUN ls -l /var/www/ &&  php /var/www/html/tools/db/build/build_cli.php
RUN a2enmod rewrite; a2enconf keyman-site 
#    service apache2 restart
