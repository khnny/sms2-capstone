FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev \
        libonig-dev \
        tesseract-ocr \
        tesseract-ocr-eng \
    && docker-php-ext-install curl mbstring mysqli pdo pdo_mysql \
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/*

RUN printf '%s\n' \
    'file_uploads=On' \
    'upload_max_filesize=16M' \
    'post_max_size=20M' \
    'max_file_uploads=20' \
    > /usr/local/etc/php/conf.d/sms2-upload.ini

COPY . /var/www/html/

RUN rm -rf /var/www/html/.git /var/www/html/.cursor \
    && find /var/www/html -type f \( -name '*.bak' -o -name '*.preprefix.bak' \) -delete \
    && find /var/www/html -type d -exec chmod 0755 {} + \
    && find /var/www/html -type f -exec chmod 0644 {} + \
    && mkdir -p /var/www/html/storage/uploads /var/www/html/storage/keys /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod 0750 /var/www/html/storage /var/www/html/storage/uploads /var/www/html/storage/keys /var/www/html/storage/logs

WORKDIR /var/www/html/

RUN test -f /var/www/html/index.php \
    && test -f /var/www/html/includes/authentication.php \
    && test -f /var/www/html/modules/crad/index.php

ENV PORT=8000

CMD ["sh", "-ec", "if [ \"${SMS2_RUN_MIGRATIONS:-0}\" = \"1\" ]; then php /var/www/html/database/migrate.php; fi; sed -i \"s/^Listen .*/Listen ${PORT:-8000}/\" /etc/apache2/ports.conf && sed -i \"s/<VirtualHost \\*:[0-9]*>/<VirtualHost *:${PORT:-8000}>/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
