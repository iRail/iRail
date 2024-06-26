# This dockerfile is for development purposes and includes xdebug
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y libssl-dev \
    zip \
    unzip \
    git \
    cron \
    curl \
    libonig-dev \
    libxml2-dev \
    libtidy-dev \
    libcurl4-gnutls-dev \
    libgnutls28-dev \
    zlib1g \
    zlib1g-dev \
    ca-certificates \
    gnupg \
    libpng-dev

# Irail/stations depends on NodeJS
RUN curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
RUN echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_18.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list
RUN apt-get update && apt-get install -y nodejs

RUN docker-php-ext-install mbstring tidy intl && docker-php-ext-enable mbstring tidy intl
RUN docker-php-ext-install pdo_mysql mysqli && docker-php-ext-enable pdo_mysql mysqli

RUN apt-get install -y libpq-dev
RUN docker-php-ext-install pdo_pgsql pgsql && docker-php-ext-enable pdo_pgsql pgsql

# Install ext-http
RUN pecl install raphf && docker-php-ext-enable raphf
RUN pecl install pecl_http

# Install graphics capabilities for e.g. APCu cache status graphs
RUN docker-php-ext-install gd && docker-php-ext-enable gd

# Install opcache
RUN docker-php-ext-install opcache

# Install igbinary for binary serialization/deserialization, saving up to 90% space while deserializing faster then serialize/deserialize
# this is crucial when caching large amounts of GTFS data
RUN pecl install igbinary
RUN docker-php-ext-enable igbinary
RUN echo "apc.serializer=igbinary" >> /usr/local/etc/php/php.ini

# Install apcu
RUN pecl install apcu
RUN echo "extension=http.so\nextension=apcu.so\napc.enable=1\napc.enable_cli=1\napc.shm_size=512M" >> /usr/local/etc/php/php.ini
RUN docker-php-ext-enable apcu http

# Install xdebug
RUN yes | pecl install xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.mode=develop,coverage,debug,profile,trace" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.output_dir=/tmp/xdebug" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.profiler_output_name=xdebug-profile.cachegrind.out.%p" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/xdebug.ini


# Installing composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /usr/local/bin/composer


WORKDIR /var/www

RUN usermod -u 1000 www-data && \
    groupmod -g 1000 www-data && \
    chown -R www-data:www-data /var/www/

RUN mkdir -p /var/log/php/ \
    && touch /var/log/php/access.log \
    && touch /var/log/php/error.log \
    && chmod 777 /var/log/php/access.log \
    && chmod 777 /var/log/php/error.log

# Copy opcache configuration
COPY docker-opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Raise memory limit, needed for handling GTFS data
RUN echo "memory_limit = 1024M" >> /usr/local/etc/php/php.ini

# Run the command on container startup
EXPOSE 8080
#CMD php -S 0.0.0.0:8080 -t /var/www/public/
