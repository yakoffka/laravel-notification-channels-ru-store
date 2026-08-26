FROM php:8.2-cli

# Создаём пользователя с UID 1000 (как на хосте)
RUN addgroup --gid 1000 appuser && \
    adduser --uid 1000 --gid 1000 --disabled-password --gecos "" appuser

# Системные зависимости
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    libpq-dev \
    libonig-dev \
    curl \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# PHP расширения
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip \
    bcmath \
    pcntl

# Установка Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

# Переключаемся на пользователя appuser
USER appuser
