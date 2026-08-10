FROM php:8.4-fpm

# ---- System packages ----
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    git \
    nginx \
    supervisor \
    gettext-base \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    && rm -rf /var/lib/apt/lists/*

# ---- PHP extensions ----
RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip

# ---- Composer ----
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --optimize-autoloader --no-dev --no-interaction

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# ---- Nginx + Supervisor config ----
COPY docker/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Render injects $PORT at runtime — nginx.conf is generated from the template on container start
EXPOSE 10000

CMD ["start.sh"]
