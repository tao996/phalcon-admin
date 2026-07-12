FROM php:8.3-fpm-alpine3.24

ENV TZ=America/Los_Angeles \
    PHALCON_VERSION=5.16.0 \
    REDIS_VERSION=6.3.0 \
    MEMCACHED_VERSION=3.4.0 \
    APCU_VERSION=5.1.28 \
    ZEPHIR_PARSER_VERSION=2.1.0 \
    IGBINARY_VERSION=3.2.17RC1 \
    PSR_VERSION=1.2.0 \
    MSGPACK_VERSION=3.0.1 \
    XLSWRITER_VERSION=3.0.0 \
    XDEBUG_VERSION=3.5.3

WORKDIR /tmp

COPY scripts/ /usr/bin/

# 强行更新系统组件消灭漏洞，并分离运行库与编译库
RUN apk update && apk upgrade --no-cache && \
    # 【运行依赖库】—— 这一组包在清理时绝对不能删！
    apk add --no-cache \
        bash unzip libzip libmemcached mariadb-connector-c libwebp \
        freetype libpng libjpeg-turbo icu-libs libpq && \
    \
    # 【编译临时库】—— 这一组包后面会被安全清理
    apk add --no-cache --virtual build-dependencies \
        build-base tzdata autoconf linux-headers \
        libzip-dev libmemcached-dev openssl-dev zlib-dev \
        mariadb-connector-c-dev freetype-dev libpng-dev libjpeg-turbo-dev \
        libwebp-dev icu-dev libpq-dev && \
    \
    # 2. 配置并安装 PHP 核心内置扩展
    docker-php-ext-configure gd --with-freetype --with-webp --with-jpeg=/usr/include/ --enable-gd && \
    docker-php-ext-install gd mysqli pdo pdo_mysql pdo_pgsql pcntl sockets bcmath exif intl opcache posix zip && \
    \
    # 3. 通过 PECL 安装第三方扩展
    pecl install zephir_parser-${ZEPHIR_PARSER_VERSION} && \
    pecl install igbinary-${IGBINARY_VERSION} && \
    pecl install psr-${PSR_VERSION} && \
    pecl install msgpack-${MSGPACK_VERSION} && \
    pecl install phalcon-${PHALCON_VERSION} && \
    pecl install redis-${REDIS_VERSION} && \
    pecl install memcached-${MEMCACHED_VERSION} && \
    pecl install apcu-${APCU_VERSION} && \
    pecl install xlswriter-${XLSWRITER_VERSION} && \
    pecl install xdebug-${XDEBUG_VERSION} && \
    docker-php-ext-enable phalcon psr sockets memcached redis apcu zephir_parser msgpack igbinary xlswriter xdebug && \
    cp /usr/share/zoneinfo/$TZ /etc/localtime && \
    echo $TZ > /etc/timezone && \
    apk del --no-cache build-dependencies && \
    rm -rf /var/cache/apk/* && \
    docker-php-source delete && \
    rm -rf /tmp/*


LABEL maintainer="authus" \
      php.version="8.3" \
      description="PHP-FPM with Phalcon 5.16.0"

# docker build -f phalcon-5.16.0.Dockerfile -t authus/phalcon:5.16.0 .