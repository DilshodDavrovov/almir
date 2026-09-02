FROM php:8.1-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
        libzip-dev libicu-dev libonig-dev libxml2-dev zlib1g-dev unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mysqli gd zip intl mbstring bcmath exif opcache \
    && rm -rf /var/lib/apt/lists/*

# Run PHP-FPM as uid 1000 so the bind-mounted project (owned by the host user) is writable
RUN useradd -u 1000 -m -s /bin/bash app

COPY almir.ini /usr/local/etc/php/conf.d/zz-almir.ini
COPY www.conf  /usr/local/etc/php-fpm.d/zz-almir.conf

WORKDIR /var/www/html
