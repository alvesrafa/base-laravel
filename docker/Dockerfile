FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    oniguruma \
    postgresql-libs \
    icu \
    libxml2 \
    supervisor \
    nginx

RUN apk add --no-cache --virtual .build-deps \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    postgresql-dev \
    icu-dev \
    libxml2-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install \
    pdo_mysql \
    pdo_pgsql \
    bcmath \
    ctype \
    fileinfo \
    mbstring \
    xml \
    intl \
    zip \
    gd 

RUN apk del .build-deps

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

RUN adduser -D -u 1000 -g www www

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-custom.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
COPY docker/php-upload.ini /usr/local/etc/php/conf.d/upload.ini

WORKDIR /var/www

COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY . .

RUN chown -R root:root .
RUN chown -R www:www storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

RUN mkdir -p storage/app/public
RUN mkdir -p storage/framework/cache/data
RUN mkdir -p storage/framework/views
RUN mkdir -p storage/framework/sessions
RUN chown -R www:www storage/framework

RUN composer run-script post-autoload-dump

RUN chown -R www:www storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache && \
    chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]