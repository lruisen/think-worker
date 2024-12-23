FROM php:8.1-cli-alpine
ENV TZ "Asia/Shanghai"

# Install system dependencies
RUN sed -i "s/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g" /etc/apk/repositories \
    && apk update && apk upgrade \
    && apk add --no-cache --virtual .build-deps oniguruma-dev libpng-dev libzip-dev freetype-dev libjpeg-turbo-dev libevent-dev openssl-dev $PHPIZE_DEPS \
    && apk add linux-headers bash oniguruma freetype libzip libpng libjpeg-turbo libevent wget \
    # ---------- Install extend ----------
    && docker-php-ext-configure gd --with-freetype=/usr/include/freetype2/freetype --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql gd bcmath mbstring zip pcntl sockets \
    && pecl channel-update pecl.php.net && pecl install event && docker-php-ext-enable --ini-name docker-php-ext-t-event.ini event \
    && pecl install redis && docker-php-ext-enable redis \
    && cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
    && sed -i "s/;curl.cainfo =/curl.cainfo=\/etc\/ssl\/certs\/ca-certificates.crt/g" /usr/local/etc/php/php.ini \
    && sed -i "s/;openssl.cafile=/openssl.cafile=\/etc\/ssl\/certs\/ca-certificates.crt/g" /usr/local/etc/php/php.ini \
    # ---------- clear works ---------- \
    && apk del .build-deps \
    && rm -rf /var/lib/apt/lists/* \
    && rm -rf /var/cache/apk/* /tmp/* /usr/share/man \
    # ---------- Install composer ----------
    && wget -nv -O /usr/local/bin/composer https://mirrors.aliyun.com/composer/composer.phar \
    && chmod u+x /usr/local/bin/composer \
    && composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/ \
    # ---------- Install success ----------
    && echo -e "\033[42;37m Build Completed :).\033[0m\n"