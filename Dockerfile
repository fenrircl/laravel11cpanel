FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git curl unzip zip \
    libzip-dev libonig-dev libxml2-dev libpq-dev \
    libcurl4-openssl-dev libpng-dev \
    libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql zip gd opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www/test

# Evitar error de git por permisos del bind mount
RUN git config --global --add safe.directory /var/www/test

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias de PHP con Composer sin ejecutar scripts como migrate
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Permisos necesarios para Laravel
RUN chown -R www-data:www-data /var/www/test \
    && chmod -R 755 storage bootstrap/cache

# Exponer el puerto del servidor embebido de Laravel
EXPOSE 8000

# Comando por defecto: iniciar servidor Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
