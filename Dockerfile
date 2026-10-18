FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql mbstring \
    && a2enmod rewrite headers

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html/storage
