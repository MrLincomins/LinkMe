FROM php:8.2-fpm

WORKDIR /var/www

RUN apt-get clean && rm -rf /var/lib/apt/lists/* \
    && apt-get update && apt-get install -y \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    build-essential \
    xz-utils \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo_mysql mbstring intl zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV NODE_VERSION=20.17.0
RUN curl -fsSL https://nodejs.org/dist/v${NODE_VERSION}/node-v${NODE_VERSION}-linux-x64.tar.xz | tar -xJ -C /usr/local --strip-components=1 \
    && npm install -g npm@latest

RUN node -v && npm -v

RUN chmod -R 755 /var/www/

RUN useradd -u 1000 -m nodeuser

RUN mkdir -p /var/www/node_modules && \
    chown -R nodeuser:nodeuser /var/www

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
COPY queue-entrypoint.sh /usr/local/bin/queue-entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/queue-entrypoint.sh

WORKDIR /var/www

USER nodeuser

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]