# --------------------------------------------------
# 1) Базовый слой: PHP 8.3 + Apache
# --------------------------------------------------
FROM php:8.3-apache

# Включаем нужные расширения
RUN apt-get update
RUN apt-get install -y --no-install-recommends libicu-dev libzip-dev libpng-dev libonig-dev
RUN yes '' | pecl install -o -f memcache \
 && echo "extension=memcache.so" > /usr/local/etc/php/conf.d/20_memcache.ini
RUN docker-php-ext-install \
        pdo_mysql \
        mbstring \
        intl \
        zip \
        gd
# Чистим кеш apt, чтобы не раздувать образ
RUN apt-get purge -y --auto-remove
RUN rm -rf /var/lib/apt/lists/*

RUN echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername
	
RUN a2enmod rewrite

RUN sed -ri 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/sites-available/000-default.conf && \
    printf '\n<Directory /var/www/html/public>\n\
        AllowOverride All\n\
    </Directory>\n' >> /etc/apache2/apache2.conf

# --------------------------------------------------
# 2) Устанавливаем Composer
# --------------------------------------------------
RUN curl -sS https://getcomposer.org/installer | php -- \
      --install-dir=/usr/local/bin --filename=composer

# --------------------------------------------------
# 3) Копируем проект и ставим зависимости
# --------------------------------------------------
WORKDIR /var/www/html

# Копируем composer.* раньше, чтобы слои кэшировались
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# --------------------------------------------------
# 4) Права для Laravel
# --------------------------------------------------
RUN chown -R www-data:www-data storage bootstrap/cache

# --------------------------------------------------
# 5) Apache уже слушает 80/tcp -> EXPOSE не нужен
#    Entrypoint/CMD унаследован: /usr/local/bin/apache2-foreground
# --------------------------------------------------
