FROM php:8.3-apache
RUN docker-php-ext-install mysqli pdo_mysql \
 && (a2dismod -f mpm_event mpm_worker || true) && a2enmod mpm_prefork rewrite headers \
 && apt-get update && apt-get install -y --no-install-recommends default-mysql-client && rm -rf /var/lib/apt/lists/*
ENV APACHE_DOCUMENT_ROOT=/var/www/html
WORKDIR /var/www/html
COPY . /var/www/html
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
 && mkdir -p application/logs application/cache application/sessions \
 && chown -R www-data:www-data application/logs application/cache application/sessions
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
CMD ["docker-entrypoint.sh"]
