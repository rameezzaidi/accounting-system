FROM php:8.3-cli
RUN docker-php-ext-install mysqli pdo_mysql \
 && apt-get update && apt-get install -y --no-install-recommends default-mysql-client && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html
COPY . /var/www/html
RUN mkdir -p application/logs application/cache application/sessions && chmod -R 777 application/logs application/cache application/sessions \
 && chmod +x docker-entrypoint.sh
CMD ["./docker-entrypoint.sh"]
