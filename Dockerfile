FROM php:8.2-apache

RUN apt-get update && apt-get install -y unzip curl git \
	&& docker-php-ext-install mysqli pdo pdo_mysql \
	&& a2enmod rewrite

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy project files
WORKDIR /var/www/html
COPY .env /var/www/html/.env
COPY entrypoint.sh /entrypoint.sh

# Set permissions and entrypoint
RUN chmod +x /entrypoint.sh
RUN chown -R www-data:www-data /var/www/html

ENTRYPOINT ["/entrypoint.sh"]
