ARG PHP_IMAGE=php:8.2-apache

FROM composer:2 AS vendor

WORKDIR /app
ARG COMPOSER_FILE=app/composer.json
ARG COMPOSER_INSTALL_FLAGS="--no-dev"
COPY ${COMPOSER_FILE} /app/composer.json
RUN composer install \
	${COMPOSER_INSTALL_FLAGS} \
	--no-interaction \
	--prefer-dist \
	--optimize-autoloader

FROM ${PHP_IMAGE}

RUN apt-get update && apt-get install -y --no-install-recommends unzip curl git \
	&& docker-php-ext-install mysqli pdo pdo_mysql \
	&& a2enmod rewrite \
	&& printf 'ServerName localhost\n' > /etc/apache2/conf-available/servername.conf \
	&& a2enconf servername \
	&& rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY app/ /var/www/html/
COPY docs/ /var/www/docs/
COPY --from=vendor /app/vendor /var/www/html/vendor
COPY entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh \
	&& chown -R www-data:www-data /var/www/html

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
