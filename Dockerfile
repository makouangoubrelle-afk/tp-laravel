# Dockerfile production — AutoChain Emmaus (Laravel 10 / PHP 8.2)
FROM php:8.2-cli-bookworm

RUN apt-get update && apt-get install -y \
    git unzip nodejs npm libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring zip bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && npm ci --omit=dev \
    && php artisan config:clear \
    && chmod -R 775 storage bootstrap/cache

ENV PORT=8080
EXPOSE 8080

CMD php artisan migrate --force \
    && php artisan serve --host=0.0.0.0 --port=${PORT}
