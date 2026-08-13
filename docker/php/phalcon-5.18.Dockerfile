FROM php:8.3-fpm-alpine

ENV TZ=America/Los_Angeles \
    PHALCON_VERSION=5.18.2 \
    REDIS_VERSION=6.3.0 \
    MEMCACHED_VERSION=3.4.0 \
    APCU_VERSION=5.1.28 \
    IGBINARY_VERSION=3.2.17RC1 \
    PSR_VERSION=1.2.0 \
    MSGPACK_VERSION=3.0.0 \
    XLSWRITER_VERSION=2.0.1 \
    XDEBUG_VERSION=3.5.1

WORKDIR /tmp

COPY scripts/ /usr/bin/
# 在网络不好时对数据进行缓存
RUN --mount=type=cache,target=/var/cache/apt \
# 1. 强行更新系统组件消灭漏洞，并分离运行库与编译库
    apk update && apk upgrade --no-cache && \
    # 【运行依赖库】—— 这一组包在清理时绝对不能删！(保留了 icu-libs 防止 intl.so 报错)
    apk add --no-cache \
        bash unzip libzip libmemcached mariadb-connector-c libwebp \
        freetype libpng libjpeg-turbo icu-libs libpq curl && \
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
    # 3. 通过 PECL 安装常规第三方扩展
    pecl install igbinary-${IGBINARY_VERSION} && \
    pecl install psr-${PSR_VERSION} && \
    pecl install msgpack-${MSGPACK_VERSION} && \
    pecl install redis-${REDIS_VERSION} && \
    pecl install memcached-${MEMCACHED_VERSION} && \
    pecl install APCu-${APCU_VERSION} && \
    pecl install xlswriter-${XLSWRITER_VERSION} && \
    pecl install xdebug-${XDEBUG_VERSION} && \
    \
    # 4. 手动拉取并编译官方 GitHub Release 的 Phalcon 5.18.2 (避开 PECL 官方索引缺包和解压路径问题)
    mkdir -p /tmp/phalcon && \
    curl -fsSL https://github.com/phalcon/cphalcon/releases/download/v${PHALCON_VERSION}/phalcon-pecl.tgz | tar -xz -C /tmp/phalcon && \
    BUILD_DIR=$(find /tmp/phalcon -name "config.m4" -exec dirname {} \; | head -n 1) && \
    cd "$BUILD_DIR" && phpize && ./configure && make -j$(nproc) && make install && \
    \
    # 5. 统一启用扩展
    docker-php-ext-enable psr sockets memcached redis apcu msgpack igbinary xlswriter xdebug phalcon && \
    \
    # 6. 配置时区与清理垃圾
    cp /usr/share/zoneinfo/$TZ /etc/localtime && \
    echo $TZ > /etc/timezone && \
    apk del --no-cache build-dependencies && \
    rm -rf /var/cache/apk/* && \
    docker-php-source delete && \
    rm -rf /tmp/*

LABEL maintainer="authus" \
      php.version="8.3" \
      description="PHP-FPM with Phalcon 5.18.2"

# 构建命令：
# docker build -f phalcon-5.18.Dockerfile -t authus/phalcon:5.18 .