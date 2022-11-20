FROM php:8.0-fpm

RUN apt-get update && apt-get install -y libssl-dev \
    zip \
    unzip \
    git \
    npm \
    cron \
    curl \
    libonig-dev \
    libxml2-dev \
    libtidy-dev

RUN docker-php-ext-install pdo_mysql mbstring tidy && docker-php-ext-enable tidy
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Install apcu
RUN pecl install apcu && docker-php-ext-enable apcu
RUN echo "extension=apcu.so" > /usr/local/etc/php/php.ini
RUN echo "apc.enable_cli=1" > /usr/local/etc/php/php.ini
RUN echo "apc.enable=1" > /usr/local/etc/php/php.ini

# Add crontab file in the cron directory
ADD crontab /etc/cron.d/myCrontab

# Give execution rights on the cron job
RUN chmod 0644 /etc/cron.d/myCrontab

# Create the log file to be able to run tail
RUN touch /var/log/cron.log

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
CMD cron && tail -f /var/log/cron.log
