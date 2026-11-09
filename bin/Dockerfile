# Test/build image: the SynergyCP PHP 8.3 base image plus Composer.
FROM synergycp/bm-php-base:php8.3

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Match the CI runner: surface deprecations and warnings instead of hiding them.
USER root
RUN mkdir -p /usr/local/etc/php/conf.d \
 && printf 'error_reporting=E_ALL\ndisplay_errors=stderr\n' > /usr/local/etc/php/conf.d/zz-error-reporting.ini
USER www

ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app
ENTRYPOINT []
CMD ["bash"]
