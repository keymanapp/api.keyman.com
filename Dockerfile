# syntax=docker/dockerfile:1
FROM php:8.4-apache@sha256:51da594c844a97f31b1cd6b1ac6660982f40788f4fe13e75f7fd39e2f9b58651 AS composer-builder

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
FROM php:8.4-apache@sha256:51da594c844a97f31b1cd6b1ac6660982f40788f4fe13e75f7fd39e2f9b58651
COPY resources/keyman-site.conf /etc/apache2/conf-available/
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
RUN echo memory_limit = 1024M >> /usr/local/etc/php/php.ini
RUN chown -R www-data:www-data /var/www/html/

# Install SQL drivers
# https://learn.microsoft.com/en-us/sql/connect/php/installation-tutorial-linux-mac?view=sql-server-ver16
# https://stackoverflow.com/a/72176870
RUN apt-get update && apt-get install -y gnupg2

# Adding custom MS repo for Debian Trixie (13)
# https://learn.microsoft.com/en-us/linux/packages
# 'debian', '13' values from /etc/os-release
RUN curl -sSL -O https://packages.microsoft.com/config/debian/13/packages-microsoft-prod.deb
RUN apt install ./packages-microsoft-prod.deb
RUN rm packages-microsoft-prod.deb
RUN apt-get update

## Install SQL Server drivers and Zip
RUN ACCEPT_EULA=Y apt-get -y --no-install-recommends install msodbcsql18 unixodbc-dev zip libzip-dev
RUN pecl install sqlsrv-5.10.1
RUN pecl install pdo_sqlsrv-5.10.1
RUN docker-php-ext-install pdo pdo_mysql zip
RUN docker-php-ext-enable sqlsrv pdo_sqlsrv pdo pdo_mysql

# Install intl extension
RUN apt-get -y install libicu-dev
RUN docker-php-ext-install intl
RUN docker-php-ext-enable intl

# Copy all composer libraries in
COPY --from=composer-builder /composer/vendor /var/www/vendor

RUN a2enmod rewrite; a2enconf keyman-site
