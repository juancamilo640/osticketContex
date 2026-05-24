FROM php:8.1-apache

# 1. Instalar herramientas y extensiones necesarias
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    wget \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli zip intl xml opcache

# 2. Habilitar reescritura de Apache
RUN a2enmod rewrite

# 3. Limpiar la carpeta del servidor por si acaso
RUN rm -rf /var/www/html/*

# 4. Descargar osTicket original de fábrica directamente al servidor
# Usamos la versión oficial estable 1.18.1
RUN wget https://github.com/osTicket/osTicket/releases/download/v1.18.1/osTicket-v1.18.1.zip -O /tmp/osticket.zip \
    && unzip /tmp/osticket.zip -d /tmp/osticket \
    && cp -r /tmp/osticket/upload/* /var/www/html/ \
    && rm -rf /tmp/osticket.zip /tmp/osticket

# 5. Configurar el archivo base de configuración (el que creamos antes)
RUN mv /var/www/html/include/ost-sampleconfig.php /var/www/html/include/ost-config.php

# 6. Configuración de PHP para evitar pantallas en blanco y ver errores
RUN echo "output_buffering = On" > /usr/local/etc/php/conf.d/osticket-settings.ini \
    && echo "display_errors = On" >> /usr/local/etc/php/conf.d/osticket-settings.ini \
    && echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/osticket-settings.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/osticket-settings.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/osticket-settings.ini

# 7. Asignar permisos correctos de Linux (Clave para que no dé Error 500)
RUN chown -R www-data:www-data /var/www/html/ \
    && find /var/www/html/ -type d -exec chmod 755 {} \; \
    && find /var/www/html/ -type f -exec chmod 666 {} \; \
    && chmod 777 /var/www/html/include/ost-config.php

EXPOSE 80