FROM php:8.1-apache

# 1. Instalar herramientas y extensiones necesarias
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli zip intl xml opcache

# 2. Habilitar reescritura de Apache
RUN a2enmod rewrite

# 3. Limpiar la carpeta por defecto de Apache
RUN rm -rf /var/www/html/*

# 4. Copiar los archivos de tu computadora al contenedor
COPY . /var/www/html/

# 5. Crear el archivo de configuración solo si no existe para evitar fallos
RUN if [ ! -f /var/www/html/include/ost-config.php ] && [ -f /var/www/html/include/ost-sampleconfig.php ]; then \
        cp /var/www/html/include/ost-sampleconfig.php /var/www/html/include/ost-config.php; \
    fi

# 6. CONFIGURACIÓN DE PHP Y FORZADO DE SSL PARA MYSQL
RUN echo "output_buffering = On" > /usr/local/etc/php/conf.d/osticket-settings.ini \
    && echo "display_errors = Off" >> /usr/local/etc/php/conf.d/osticket-settings.ini \
    && echo "display_startup_errors = Off" >> /usr/local/etc/php/conf.d/osticket-settings.ini \
    && echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_NOTICE" >> /usr/local/etc/php/conf.d/osticket-settings.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/osticket-settings.ini \
    && echo "mysqli.default_ssl = On" >> /usr/local/etc/php/conf.d/osticket-settings.ini

# 7. Asignar permisos correctos de Linux de forma segura
RUN chown -R www-data:www-data /var/www/html/ \
    && find /var/www/html/ -type d -exec chmod 755 {} \; \
    && find /var/www/html/ -type f -exec chmod 666 {} \;

EXPOSE 80