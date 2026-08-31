FROM php:8.1-cli

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

RUN cp .env.example .env
RUN sed -i 's/\r$//' .env
RUN php artisan package:discover --ansi || true

EXPOSE 8000

CMD ["sh", "-c", "php artisan config:clear && php artisan key:generate --force && php artisan migrate --force && php artisan view:cache && php artisan serve --host=0.0.0.0 --port=8000"]
