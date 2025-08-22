FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    libzip-dev \
    icu-dev \
    oniguruma-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    intl \
    mbstring

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Set permissions for future volume mounts
RUN mkdir -p var/cache var/log var/sessions && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
