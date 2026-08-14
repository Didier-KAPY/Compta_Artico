FROM php:8.2-apache

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    nodejs \
    npm \
    ca-certificates \
    && docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Copier le projet Laravel
COPY . .

# Certificat SSL Aiven
COPY aiven-ca.pem /etc/ssl/certs/aiven-ca.pem

RUN chmod 644 /etc/ssl/certs/aiven-ca.pem

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# Dépendances PHP
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Dépendances frontend
RUN npm install \
    && npm run build

# Permissions Laravel
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Laravel doit être servi depuis /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri \
    -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# Autoriser le .htaccess de Laravel
RUN printf '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' \
    > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

# Render attend le serveur sur le port 10000
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:10000>/' \
    /etc/apache2/sites-available/000-default.conf

EXPOSE 10000

CMD ["apache2-foreground"]