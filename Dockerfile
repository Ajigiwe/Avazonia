# Avazonia — Local development image
# PHP 8.2 + Apache to match MariaDB 10.4 production dump
FROM php:8.2-apache

# System deps + PHP extensions
RUN apt-get update && apt-get install -y \
        libpng-dev \
        libjpeg-dev \
        libwebp-dev \
        libfreetype6-dev \
        libzip-dev \
        unzip \
        curl \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql mysqli zip \
    && a2enmod rewrite headers expires \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache: allow .htaccess overrides and set DocumentRoot
RUN sed -ri -e 's!/var/www/html!/var/www/html!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/!/var/www/html!g' /etc/apache2/apache2.conf \
    && printf '<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/avazonia.conf \
    && a2enconf avazonia

# PHP settings for local dev (upload is 15-30MB images)
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

WORKDIR /var/www/html

# Copy entrypoint that ensures upload dirs exist before Apache starts
COPY docker/entrypoint.sh /usr/local/bin/avazonia-entrypoint.sh
RUN chmod +x /usr/local/bin/avazonia-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["avazonia-entrypoint.sh"]
CMD ["apache2-foreground"]
