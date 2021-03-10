#!/bin/bash

set -e -x

# Debian stuff

#Suppression des repos buster présents dans l'image
#rm /etc/apt/sources.list.d/buster.list
#rm /etc/apt/preferences.d/argon2-buster

# For certbot
echo 'deb http://ftp.debian.org/debian stretch-backports main' >  /etc/apt/sources.list.d/stretch.backport.list

apt-get update

apt-get install -y \
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
    netcat

apt-get install -y -t stretch-backports python-certbot-apache

rm -r /var/lib/apt/lists/*

# Locale
sed -i -e 's/# fr_FR.UTF-8 UTF-8/fr_FR.UTF-8 UTF-8/' /etc/locale.gen
echo 'LANG="fr_FR.UTF-8"'>/etc/default/locale
dpkg-reconfigure --frontend=noninteractive locales
update-locale LANG=fr_FR.UTF-8
echo "Europe/Paris" > /etc/timezone
dpkg-reconfigure -f noninteractive tzdata

# PHP Stuff

pecl install \
      redis \
      xdebug

docker-php-ext-configure gd --with-jpeg-dir=/usr/include/
docker-php-ext-configure imap --with-kerberos --with-imap-ssl

docker-php-ext-enable \
      redis \
      xdebug

docker-php-ext-install \
    gd \
    imap \
    pcntl \
    pdo \
    pdo_pgsql \
    pgsql \
    zip

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
