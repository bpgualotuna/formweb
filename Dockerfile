FROM php:8.2-apache

RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-mongodb
RUN chown -R www-data:www-data /var/www/html

RUN sed -i 's|/var/www/html|/var/www/html/public|g' \
        /etc/apache2/sites-available/000-default.conf \
        /etc/apache2/apache2.conf

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
