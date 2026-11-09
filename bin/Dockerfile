# Test/build image: the SynergyCP PHP 8.3 base image plus Composer.
FROM synergycp/bm-php-base:php8.3

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app
ENTRYPOINT []
CMD ["bash"]
