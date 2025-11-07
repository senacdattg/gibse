FROM php:8.2-apache

LABEL maintainer="SENA - Gestión Integral de la Biodiversidad"
LABEL description="Aplicación web PHP para el programa de Tecnología en Gestión Integral de la Biodiversidad"

ENV APACHE_DOCUMENT_ROOT=/var/www/html

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN a2enmod rewrite headers expires

COPY . ${APACHE_DOCUMENT_ROOT}/

RUN chown -R www-data:www-data ${APACHE_DOCUMENT_ROOT} \
    && chmod -R 755 ${APACHE_DOCUMENT_ROOT}

WORKDIR ${APACHE_DOCUMENT_ROOT}

EXPOSE 80

CMD ["apache2-foreground"]

