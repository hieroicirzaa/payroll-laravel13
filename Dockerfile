FROM php:8.3-fpm-bookworm

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        bash \
        ca-certificates \
        curl \
        git \
        unzip \
        zip \
        libcurl4-openssl-dev \
        libicu-dev \
        libonig-dev \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libxml2-dev \
        fontconfig \
        fonts-dejavu-core \
        nodejs \
        npm \
        postgresql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_pgsql \
        pgsql \
        zip \
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Pre-install Composer dependencies during image build.
# The project folder is bind-mounted in development, so the prepared vendor
# directory is also copied to /opt/payroll/vendor and restored by entrypoint
# when the named Docker volume payroll_vendor is empty.
COPY composer.json ./
RUN composer install \
        --prefer-dist \
        --no-interaction \
        --no-progress \
        --optimize-autoloader \
        --no-scripts \
    && mkdir -p /opt/payroll \
    && cp -a vendor /opt/payroll/vendor \
    && composer clear-cache

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-payroll.ini
COPY docker/php/entrypoint.sh /usr/local/bin/payroll-entrypoint
RUN chmod +x /usr/local/bin/payroll-entrypoint

EXPOSE 9000

ENTRYPOINT ["payroll-entrypoint"]
CMD ["php-fpm"]
