FROM php:8.1-apache

# Instalar herramientas básicas, git, unzip y extensiones PHP esenciales para osTicket
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    git \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli zip intl xml opcache

# Instalar Composer de forma oficial dentro del contenedor
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuración para evitar errores de Headers
RUN echo "output_buffering = On" > /usr/local/etc/php/conf.d/osticket-settings.ini \
    && echo "display_errors = On" >> /usr/local/etc/php/conf.d/osticket-settings.ini \
    && echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/osticket-settings.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/osticket-settings.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/osticket-settings.ini

# Activar el módulo rewrite de Apache
RUN a2enmod rewrite

# Copiar el código del proyecto al servidor
COPY . /var/www/html/

# TRUCO MAESTRO: Entrar a la carpeta con problemas y forzar la reparación de dependencias
RUN cd /var/www/html/include/laminas-mail && composer install --no-dev --optimize-autoloader || true

# Asegurar permisos correctos de lectura y ejecución
RUN chown -R www-data:www-data /var/www/html/ \
    && find /var/www/html/ -type d -exec chmod 755 {} \; \
    && find /var/www/html/ -type f -exec chmod 644 {} \;

EXPOSE 80