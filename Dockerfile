# Dockerfile production — AutoChain Emmaus (Laravel 10 / PHP 8.2)
FROM php:8.2-cli-bookworm

RUN apt-get update && apt-get install -y \
    git unzip nodejs npm libzip-dev libpng-dev libonig-dev libxml2-dev libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring zip bcmath fileinfo \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN cp .env.example .env \
    && composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && composer dump-autoload --optimize \
    && npm install dotenv@^17.4.2 ethers@^6.17.0 --no-save --no-audit --no-fund \
    && sed -i 's/\r$//' start.sh \
    && chmod +x start.sh \
    && chmod -R 775 storage bootstrap/cache

ENV PORT=8080
EXPOSE 8080

CMD ["sh", "start.sh"]
