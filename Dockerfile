FROM php:8.1-apache

# Instalar herramientas básicas y extensiones PHP esenciales para osTicket
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli zip intl xml opcache

# TRUCO MÁGICO: Activar output_buffering para evitar errores de Headers en osTicket
RUN echo "output_buffering = On" > /usr/local/etc/php/conf.d/osticket-settings.ini

# Activar el módulo rewrite de Apache requerido por osTicket
RUN a2enmod rewrite

# Copiar el código del proyecto al directorio web de Apache
COPY . /var/www/html/

# Asegurar los permisos correctos de lectura y escritura
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

EXPOSE 80