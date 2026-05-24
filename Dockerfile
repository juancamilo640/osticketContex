FROM php:8.1-apache

# Instalar extensiones PHP esenciales para osTicket
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli zip intl xml opcache

# Configuración obligatoria de PHP para osTicket en Render
RUN echo "output_buffering = On" > /usr/local/etc/php/conf.d/osticket-settings.ini \
    && echo "display_errors = On" >> /usr/local/etc/php/conf.d/osticket-settings.ini \
    && echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/osticket-settings.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/osticket-settings.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/osticket-settings.ini

# Activar módulo rewrite
RUN a2enmod rewrite

# Copiar el código
COPY . /var/www/html/

# Forzar la propiedad total a Apache para evitar bloqueos
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 775 /var/www/html/

EXPOSE 80