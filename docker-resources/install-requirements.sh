#!/bin/bash

set -e -x

# Debian stuff

export DEBIAN_FRONTEND=noninteractive

apt-get update

apt-get install -y  --no-install-recommends \
    clamdscan \
    cron \
    git \
    libc-client-dev \
    libjpeg-dev \
    libkrb5-dev \
    libpng-dev \
    libpq-dev \
    locales \
    logrotate \
    msmtp \
    sudo \
    supervisor \
    xmlsec1 \
    xmlstarlet \
    wget \
    zip \
    netcat \
    poppler-utils \
    tzdata \
    apache2 \
    ca-certificates \
    certbot \
    php \
    php-cli \
    php-curl \
    php-gd \
    php-imap \
    php-pgsql \
    php-imagick \
    php-pear \
    php-zip \
    redis-tools \
    php-mbstring \
    curl \
    php-dev \
    php-redis \
    php-pdo \
    php-intl
    #\
    #python-certbot-apache TODO : non dispo, doit-on supprimer ??


rm -r /var/lib/apt/lists/*

# Locale
sed -i -e 's/# fr_FR.UTF-8 UTF-8/fr_FR.UTF-8 UTF-8/' /etc/locale.gen
echo 'LANG="fr_FR.UTF-8"'>/etc/default/locale
dpkg-reconfigure --frontend=noninteractive locales
update-locale LANG=fr_FR.UTF-8
echo "Europe/Paris" > /etc/timezone
dpkg-reconfigure -f noninteractive tzdata

# Fix specific problem with Debian/libcurl/let'encrypt  https://serverfault.com/a/1079226
sed -i '/^mozilla\/DST_Root_CA_X3/s/^/!/' /etc/ca-certificates.conf && update-ca-certificates -f

#Suite site pear down suite à attaque
cd /tmp
wget https://ressources.libriciel.fr/deploiement/m/mail-v1.4.1.tar.gz \
      https://ressources.libriciel.fr/deploiement/m/mail_mime-1.10.2.tar.gz

pear install \
    mail-v1.4.1.tar.gz \
    mail_mime-1.10.2.tar.gz

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin
mv /usr/local/bin/composer.phar /usr/local/bin/composer