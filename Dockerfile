FROM php:8.2-fpm

# Install PHP and system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    zip unzip \
    curl git gnupg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Node.js (v18) and npm
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install and build frontend assets
RUN npm install && npm run build

# Set proper permissions for Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Expose Laravel dev server port
EXPOSE 8000

# Start Laravel development server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
