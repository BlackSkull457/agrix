FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli pdo pdo_mysql gd zip

# Completely remove conflicting MPM modules before enabling mpm_prefork
RUN apt-get remove -y apache2-mpm-worker apache2-mpm-event || true && \
    a2dismod mpm_event mpm_worker || true && \
    rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* && \
    a2enmod mpm_prefork rewrite

# Copy Apache configuration files
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
COPY mpm.conf /etc/apache2/mods-available/mpm_prefork.conf

# Ensure only mpm_prefork is loaded
RUN mkdir -p /etc/apache2/mods-enabled && \
    ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load && \
    ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf && \
    rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*

COPY . /var/www/html/
COPY entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh && \
    chown -R www-data:www-data /var/www/html

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]