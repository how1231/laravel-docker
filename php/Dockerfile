FROM php:8.0-apache

# mod rewrite
RUN a2enmod rewrite

# update apt
RUN apt-get update

# install apt libraries
RUN echo "\e[1;33mInstall apt libraries\e[0m"
RUN apt-get -y install \
    nano \
    wget \
    dialog \
    vim \
    apt-utils \
    build-essential \
    git \
    curl \
    libcurl4 \
    libcurl4-openssl-dev \
    zlib1g-dev \
    libzip-dev \
    zip \
    libbz2-dev \
    locales \
    libmcrypt-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    libmariadb-dev \
    unzip zip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev
    
# install php extension
RUN docker-php-ext-install gettext pdo pdo_mysql gd

RUN echo "\e[1;33mInstall php extension\e[0m"
RUN docker-php-ext-install \
    intl \
    exif \
    pcntl \
    bcmath \
    ctype \
    curl \
    iconv \
    xml \
    soap \
    mbstring \
    tokenizer \
    bz2 \
    zip

# configure gd
RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# get composer image
COPY --from=composer:2.4 /usr/bin/composer /usr/bin/composer