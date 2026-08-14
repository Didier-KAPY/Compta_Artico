FROM php:8.2-cli

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    nodejs \
    npm \
    ca-certificates \
    && docker-php-ext-install pdo_mysql \
    && update-ca-certificates \
    && rm -rf /var/lib/apt/lists/*

COPY . .

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

RUN composer install --no-dev --optimize-autoloader

RUN npm install && npm run build

RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 10000

CMD php artisan optimize:clear \
    && php artisan migrate --force \
    && php artisan serve --host=0.0.0.0 --port=10000