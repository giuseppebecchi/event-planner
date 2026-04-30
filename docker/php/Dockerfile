FROM php:8.2-fpm

# -------------------------------------------------
# System deps + MongoDB extension
# -------------------------------------------------
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libicu-dev \
    libzip-dev \
    locales \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install gd intl zip pdo_mysql  \
    && rm -rf /var/lib/apt/lists/*

# -------------------------------------------------
# Locale & timezone
# -------------------------------------------------
ENV TZ=UTC \
    LANG=en_US.UTF-8 \
    LANGUAGE=en_US:en \
    LC_ALL=en_US.UTF-8 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/composer

RUN sed -i 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen \
    && locale-gen

# -------------------------------------------------
# Composer (multi-stage, ufficiale)
# -------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# -------------------------------------------------
# Workdir
# -------------------------------------------------
WORKDIR /var/www

# Upload limits for Filament / Livewire file uploads
RUN { \
    echo 'upload_max_filesize=20M'; \
    echo 'post_max_size=20M'; \
    echo 'memory_limit=256M'; \
} > /usr/local/etc/php/conf.d/uploads.ini
