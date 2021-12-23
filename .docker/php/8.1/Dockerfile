FROM arm64v8/php:8.1-cli-bullseye

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH=$HOME/.composer/vendor/bin:$PATH

# Download script to install PHP extensions and dependencies
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod uga+x /usr/local/bin/install-php-extensions && sync

# install system utilities
RUN apt-get update && ACCEPT_EULA=Y apt-get install -y \
    curl \
    nano \
    zip unzip

# Install dev libraries
RUN apt-get update && ACCEPT_EULA=Y apt-get install -y \
    libicu-dev \
    libpq-dev \
    libxml2-dev \
    librdkafka-dev

# Install PHP
RUN install-php-extensions \
      bz2 \
      pcntl \
      intl \
      mbstring \
      zip \
      sockets \
      rdkafka \
      xdebug

# Clean Apt Manager
RUN apt autoremove -y \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
 && /bin/bash -c "source ~/.bashrc"

# Install Composer.
RUN mkdir -p $HOME/.composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && ln -s $(composer config --global home) $HOME/.composer

# Configure PHP
COPY .docker/php/8.1/conf/conf.d/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
COPY .docker/php/8.1/conf/php.development.ini /usr/local/etc/php/php.ini

WORKDIR /var/www/html

RUN echo 'ping localhost &' > /bootstrap.sh
RUN echo 'sleep infinity' >> /bootstrap.sh
RUN chmod +x /bootstrap.sh

CMD /bootstrap.sh
