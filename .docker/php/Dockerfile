FROM php:8.1-fpm-alpine

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH=$HOME/.composer/vendor/bin:$PATH

# Download script to install PHP extensions and dependencies
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod uga+x /usr/local/bin/install-php-extensions && sync

# install system utilities
RUN apk add --no-cache \
    curl \
    nano \
    git \
    zip unzip

# Install dev libraries
RUN apk add --no-cache \
    icu-dev \
    libpq-dev \
    libxml2-dev \
    librdkafka-dev \
    unixodbc-dev

# Install PHP
RUN install-php-extensions \
    bz2 \
    pcntl \
    intl \
    zip \
    sockets \
    xdebug-stable \
    rdkafka-stable \
    @composer

# Configure PHP
COPY conf/conf.d/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
COPY conf/php.development.ini /usr/local/etc/php/php.ini

WORKDIR /var/www/html

CMD sleep infinity
