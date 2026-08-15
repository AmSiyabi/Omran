FROM php:8.5-cli

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN install-php-extensions pdo_mysql bcmath intl zip gd exif pcntl opcache redis \
    && apt-get update && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer/composer:2-bin /composer /usr/local/bin/composer

# artisan serve runs the CLI SAPI — without this every request re-parses the
# framework from the (slow) Windows bind mount
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=1'; \
        echo 'opcache.memory_consumption=192'; \
        echo 'opcache.validate_timestamps=1'; \
        # stat() on the Windows bind mount costs ~2.5ms/file — revalidating
        # ~700 framework files each second added ~1.8s to most requests.
        # Trade-off: PHP edits can take up to 15s to appear via artisan serve.
        echo 'opcache.revalidate_freq=15'; \
        echo 'realpath_cache_size=4096K'; \
        echo 'realpath_cache_ttl=600'; \
    } > /usr/local/etc/php/conf.d/zz-dev-performance.ini

WORKDIR /var/www/html

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
