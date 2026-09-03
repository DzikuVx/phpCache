FROM php:8.2-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libmemcached-dev \
        libonig-dev \
        zlib1g-dev \
    && docker-php-ext-install mbstring \
    && pecl install memcached \
    && docker-php-ext-enable memcached \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
