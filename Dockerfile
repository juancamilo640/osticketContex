FROM php:8.1-apache

# Instalar extensiones necesarias para osTicket
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libintl-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd intl mysqli xml zip opcache

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Copiar los archivos del proyecto al servidor web
COPY . /var/www/html/

# Ajustar permisos para osTicket
RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80