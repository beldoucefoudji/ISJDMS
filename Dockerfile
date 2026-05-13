# Use the official PHP image with Apache
FROM php:8.2-apache

# Install MySQL extension for your mysqli code
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy your project files to the server's web folder
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80