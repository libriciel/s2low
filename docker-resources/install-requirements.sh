#!/bin/bash

set -e -x

if [ ${GID} -ne 33 ] ; then
  addgroup --gid "${GID}" "${GROUPNAME}"
fi

if [ ${UID} -ne 33 ] ; then
  adduser --uid "${UID}" --gid "${GID}" --gecos "" --disabled-password --no-create-home "${USERNAME}"
fi

# Debian stuff

export DEBIAN_FRONTEND=noninteractive

apt-get update

apt-get install -y  --no-install-recommends \
    clamdscan \
    cron \
    git \
    locales \
    logrotate \
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
    php-redis \
    php-pdo \
    php-intl \
    php-ssh2 \
    file
    #\
    #python-certbot-apache TODO : non dispo, doit-on supprimer ??

rm -r /var/lib/apt/lists/*

rm /etc/cron.d/certbot

# Locale
sed -i -e 's/# fr_FR.UTF-8 UTF-8/fr_FR.UTF-8 UTF-8/' /etc/locale.gen
echo 'LANG="fr_FR.UTF-8"'>/etc/default/locale
dpkg-reconfigure --frontend=noninteractive locales
update-locale LANG=fr_FR.UTF-8
echo "Europe/Paris" > /etc/timezone
dpkg-reconfigure -f noninteractive tzdata

# Fix specific problem with Debian/libcurl/let'encrypt  https://serverfault.com/a/1079226
sed -i '/^mozilla\/DST_Root_CA_X3/s/^/!/' /etc/ca-certificates.conf && update-ca-certificates -f

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin
mv /usr/local/bin/composer.phar /usr/local/bin/composer

chmod u+s /usr/sbin/cron

