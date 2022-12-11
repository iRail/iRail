# This dockerfile is for development purposes and includes xdebug
FROM php:8.2-fpm


RUN apt-get update && apt-get install -y libssl-dev \
    zip \
    unzip \
    git \
    cron \
    curl \
    libonig-dev \
    libxml2-dev \
    libtidy-dev

# Irail/stations depends on NodeJS
RUN curl -fsSL https://deb.nodesource.com/setup_16.x | bash -
RUN apt-get update && apt-get install -y nodejs


RUN docker-php-ext-install pdo_mysql mbstring tidy && docker-php-ext-enable pdo_mysql mbstring tidy

# Install apcu
RUN pecl install apcu && docker-php-ext-enable apcu
RUN echo "extension=apcu.so\napc.enable=1" > /usr/local/etc/php/php.ini

# Install xdebug
RUN yes | pecl install xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.mode=develop,coverage,debug,profile,trace" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini

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

# Run the command on container startup
EXPOSE 8080
CMD php -S 0.0.0.0:8080 -t /var/www/public/
