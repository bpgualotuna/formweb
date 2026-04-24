FROM php:8.2-apache

# Instalar extensión mongodb (sin Composer, sin pecl manual)
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions mongodb

WORKDIR /var/www/html
COPY . .

RUN chown -R www-data:www-data /var/www/html

# Apuntar DocumentRoot de Apache a public/
RUN sed -i 's|/var/www/html|/var/www/html/public|g' \
        /etc/apache2/sites-available/000-default.conf \
        /etc/apache2/apache2.conf

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
