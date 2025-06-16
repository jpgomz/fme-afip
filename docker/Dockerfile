FROM php:8.2-fpm

# Definir variables de usuario
ARG UID=1000
ARG GID=1000

# Instalar dependencias necesarias, incluyendo vim
RUN apt-get update && apt-get install -y \
    vim \
    git \
    unzip \
    curl \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
    autoconf \
    gcc \
    make \
    pkg-config \
    && docker-php-ext-install soap zip \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && rm -rf /var/lib/apt/lists/*

COPY ./xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Crear grupo y usuario con el mismo UID/GID que el host
RUN addgroup --gid $GID appgroup && adduser --uid $UID --gid $GID --disabled-password --gecos "" appuser

# Usar el usuario creado
USER appuser
