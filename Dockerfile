ARG PHP_BASE=php:8.3.33-apache
FROM ${PHP_BASE}
RUN apt-get update && apt-get install -y libpng-dev libjpeg62-turbo-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql gd \
    && rm -rf /var/lib/apt/lists/*
RUN printf 'upload_max_filesize=10M\npost_max_size=12M\nmemory_limit=256M\ndate.timezone=America/Guayaquil\nexpose_php=Off\n' > /usr/local/etc/php/conf.d/zzzz-recorridos.ini
COPY docker-apache.conf /etc/apache2/conf-enabled/app.conf
WORKDIR /var/www/html
RUN find /var/www/html -mindepth 1 -maxdepth 1 -exec rm -rf {} +
COPY . .
RUN mkdir -p storage/evidencias && chown -R www-data:www-data storage
