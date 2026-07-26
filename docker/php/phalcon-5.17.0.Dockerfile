FROM php:8.3-fpm-alpine3.24

ENV TZ=America/Los_Angeles \
    PHALCON_VERSION=5.17.0 \
    ZEPHIR_PARSER_VERSION=2.4.0

# 1. 复制官方的 install-php-extensions 脚本，或者直接用 --mount 从它的镜像里拷
COPY --from=ghcr.io/mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

# 2. 一行命令搞定所有核心、第三方扩展及 Phalcon（它会自动处理依赖、下载最新版、清理垃圾）
RUN install-php-extensions \
    gd \
    mysqli \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    pcntl \
    sockets \
    bcmath \
    exif \
    intl \
    opcache \
    posix \
    zip \
    sysvmsg \
    sysvsem \
    sysvshm \
    igbinary \
    msgpack \
    redis \
    memcached \
    apcu \
    xlswriter \
    xdebug \
    event \
    psr \
    zephir_parser-${ZEPHIR_PARSER_VERSION} \
    phalcon-${PHALCON_VERSION}

LABEL maintainer="authus" \
      php.version="8.3" \
      description="PHP-FPM with Phalcon 5.17.0"

# docker build -f phalcon-5.17.0.Dockerfile -t authus/phalcon:5.17.0 .
