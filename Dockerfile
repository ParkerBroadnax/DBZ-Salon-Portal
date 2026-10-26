FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod headers \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf 'Header always set X-Content-Type-Options "nosniff"\nHeader always set X-Frame-Options "DENY"\nHeader always set Referrer-Policy "same-origin"\n' \
        > /etc/apache2/conf-enabled/security-headers.conf

COPY . /var/www/html

# Create any missing tables (schema files use IF NOT EXISTS) before starting Apache, so an existing
# database volume picks up schema changes; MariaDB only runs its init scripts on a fresh volume.
CMD ["sh", "-c", "php bin/init-db.php && exec apache2-foreground"]
