# Use PHP 8.2 with FPM
FROM php:8.2-fpm-alpine

# Install mysqli extension for database
RUN docker-php-ext-install mysqli

# Set working directory
WORKDIR /var/www/html

# Copy project files into container
COPY . /var/www/html
