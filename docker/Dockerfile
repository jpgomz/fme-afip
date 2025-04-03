FROM php:8.2-fpm

# Definir variables de usuario
ARG UID=1000
ARG GID=1000

# Instalar dependencias necesarias, incluyendo vim
RUN apt-get update && apt-get install -y vim \
    && apt-get install -y git unzip curl \
    && rm -rf /var/lib/apt/lists/*

# Crear grupo y usuario con el mismo UID/GID que el host
RUN addgroup --gid $GID appgroup && adduser --uid $UID --gid $GID --disabled-password --gecos "" appuser

# Usar el usuario creado
USER appuser
