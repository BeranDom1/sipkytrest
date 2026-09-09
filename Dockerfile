FROM php:8.4-apache

RUN docker-php-ext-install mysqli \
    && a2enmod rewrite headers \
    && printf '%s\n' \
        'display_errors=Off' \
        'log_errors=On' \
        'expose_php=Off' \
        'session.cookie_httponly=1' \
        'session.cookie_samesite=Lax' \
        > /usr/local/etc/php/conf.d/sipky-trest.ini \
    && printf '%s\n' \
        '<Directory /var/www/html>' \
        '    AllowOverride All' \
        '    Require all granted' \
        '</Directory>' \
        > /etc/apache2/conf-available/sipky-trest.conf \
    && a2enconf sipky-trest

WORKDIR /var/www/html

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD php -r '$socket = @fsockopen("127.0.0.1", 80, $errno, $error, 3); exit($socket ? 0 : 1);'

CMD ["apache2-foreground"]
