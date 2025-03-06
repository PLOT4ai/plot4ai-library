# Use official PHP image
FROM php:8.1-cli as base

# Install required system dependencies
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libxrender1 \
    libfontconfig \
    zip \
    unzip \
    git \
    jq \
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j$(nproc) gd

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy only composer.json
COPY composer.json ./

# Install PHP dependencies
RUN composer install --no-dev

# Copy all application files into the container
COPY . .

# create cache folder
RUN mkdir -p /app/scripts/cache/qr

# Command to run the PHP script
ENTRYPOINT ["bash", "scripts/generate-all-pdfs.sh"]
