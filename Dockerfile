FROM php:8.1-apache

# Instalar dependencias del sistema de forma segura
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libintl-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/list/apt/lists/*

# Configurar e instalar extensiones de PHP requeridas por osTicket
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd intl mysqli xml zip opcache

# Habilitar el módulo de reescritura para los enlaces de osTicket
RUN a2enmod rewrite

# Copiar el código al directorio del servidor Apache
COPY . /var/www/html/

# Asegurar permisos correctos para que funcione el instalador web
RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80