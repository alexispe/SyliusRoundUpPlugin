FROM node:14-alpine AS node
FROM composer:2.3 AS composer

FROM alpine:3.18

ARG SYLIUS_PHP_VERSION=8.2

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY --from=node /usr/lib           /usr/lib
COPY --from=node /usr/local/share   /usr/local/share
COPY --from=node /usr/local/lib     /usr/local/lib
COPY --from=node /usr/local/include /usr/local/include
COPY --from=node /usr/local/bin     /usr/local/bin

RUN apk update --no-cache && apk add --no-cache \
    curl \
    supervisor \
    unzip \
    python3 \
    g++ \
    make \
    nginx \
    yarn \
    php82 \
    php82-apcu \
    php82-calendar \
    php82-common \
    php82-cli \
    php82-common \
    php82-ctype \
    php82-curl \
    php82-dom \
    php82-exif \
    php82-fileinfo \
    php82-fpm \
    php82-gd \
    php82-intl \
    php82-mbstring \
    php82-mysqli \
    php82-mysqlnd \
    php82-opcache \
    php82-pdo \
    php82-pdo_mysql \
    php82-pdo_pgsql \
    php82-pgsql \
    php82-phar \
    php82-session \
    php82-simplexml \
    php82-sodium \
    php82-sqlite3 \
    php82-tokenizer \
    php82-xml \
    php82-xmlwriter \
    php82-xsl \
    php82-zip

RUN rm -rf /var/lib/apk/lists/* /tmp/* /var/tmp/* /usr/share/doc/* /usr/share/man/* /var/cache/apk/*

RUN ln -s /usr/sbin/php-fpm82 /usr/sbin/php-fpm \
    && ln -sf /usr/bin/php82 /usr/bin/php \
    && mkdir -p /run/php /var/log/php-fpm

RUN adduser -u 1000 -D -S -G www-data www-data

COPY .docker/supervisord/supervisord.conf   /etc/supervisor/conf.d/supervisor.conf
COPY .docker/nginx/nginx.conf         /etc/nginx/nginx.conf
COPY .docker/php/php-fpm.conf       /etc/php82/php-fpm.conf
COPY .docker/php/php.ini            /etc/php82/php.ini

WORKDIR /app

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]
