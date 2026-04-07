FROM php:8.5-fpm

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates curl git unzip zip \
        locales \
        libicu-dev \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libzip-dev zlib1g-dev \
        libxml2-dev \
        sudo \
        gcc g++ make \
        vim \
    ; \
    echo "en_US.UTF-8 UTF-8" > /etc/locale.gen; \
    echo "fr_FR.UTF-8 UTF-8" >> /etc/locale.gen; \
    echo "pl_PL.UTF-8 UTF-8" >> /etc/locale.gen; \
    locale-gen;

# Install gd, MySQL, ZIP, Intl extensions
RUN set -eux; \
    apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y  libfreetype6-dev libjpeg62-turbo-dev libmcrypt-dev libpng-dev \
    zlib1g-dev libxml2-dev libzip-dev libonig-dev graphviz && \
    docker-php-source extract && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd && \
    docker-php-ext-configure intl && \
    docker-php-ext-install pdo_mysql zip intl && \
    docker-php-source delete

# Setup php.ini
COPY php.ini /usr/local/etc/php/php.ini
COPY php-fpm-pool.conf 	/usr/local/etc/php/pool.d/www.conf

# Install Composer
ENV COMPOSER_VERSION=2.9.3
RUN set -eux; \
    curl -sSk https://getcomposer.org/installer | php -- --disable-tls --version=${COMPOSER_VERSION} && \
    mv composer.phar /usr/local/bin/composer

# Install Symfony CLI
RUN set -eux; \
    curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | sudo -E bash && \
    sudo apt install symfony-cli

# Configure PHP error reporting
RUN set -eux; \
    { \
        echo 'error_reporting = E_ALL'; \
        echo 'display_startup_errors = On'; \
        echo 'display_errors = On'; \
    } > /usr/local/etc/php/conf.d/error_reporting.ini

# Install xdebug
RUN	set -eux; \
    pecl install xdebug && \
    docker-php-ext-enable xdebug
RUN set -eux; \
    if php -m | grep -qi xdebug; then \
        { \
            echo 'zend_extension=xdebug'; \
            echo 'xdebug.mode=develop,coverage,debug,trace'; \
            echo 'xdebug.start_with_request=trigger'; \
            echo 'xdebug.client_port=9003'; \
            echo 'xdebug.idekey=PHPSTORM'; \
            echo 'xdebug.discover_client_host=1'; \
            echo 'xdebug.var_display_max_children=128'; \
            echo 'xdebug.var_display_max_data=512'; \
            echo 'xdebug.var_display_max_depth=3'; \
        } > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
    fi

# Install Node.js
RUN	set -eux; \
    curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - && \
    apt-get install -y nodejs

# Cleanup cleanup
RUN	set -eux; \
    apt-get autoremove -y && \
    apt-get autoclean -y && \
    rm -rf /var/lib/apt/lists/*

# Setup dev user
RUN	set -eux; \
    groupadd dev -g 1000 && \
    useradd dev -g dev -d /home/dev -m && \
    passwd -d dev && \
    echo "dev ALL=(ALL) ALL" > /etc/sudoers

WORKDIR /home/wwwroot/

USER dev

EXPOSE 9000
CMD ["php-fpm"]
