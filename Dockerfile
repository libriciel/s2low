FROM php:5.5-apache

RUN apt-get update && apt-get install -y \
    clamdscan \
    cron \
    git \
    libc-client-dev \
    libjpeg-dev \
    libkrb5-dev \
    libpng-dev \
    libpq-dev \
    locales \
    pdfsam \
    pdftk \
    ssmtp \
    sudo \
    supervisor \
    xmlsec1 \
    xmlstarlet \
    wget \
    zip \
    && rm -r /var/lib/apt/lists/*

# Configuration de clamav
COPY ./docker-resources/clamav/* /etc/clamav/

# Installation de certbot
RUN echo 'deb http://ftp.debian.org/debian jessie-backports main' >  /etc/apt/sources.list.d/jessie.backport.list
RUN apt-get update && apt-get install -y -t jessie-backports \
    certbot \
    python-certbot-apache

# Gestion des locales
RUN sed -i -e 's/# fr_FR.UTF-8 UTF-8/fr_FR.UTF-8 UTF-8/' /etc/locale.gen && \
    echo 'LANG="fr_FR.UTF-8"'>/etc/default/locale&& \
    dpkg-reconfigure --frontend=noninteractive locales && \
    update-locale LANG=fr_FR.UTF-8 && \
    echo "Europe/Paris" > /etc/timezone &&\
	dpkg-reconfigure -f noninteractive tzdata


# Installation de xdebug
RUN pecl install xdebug-2.5.3 && \
    docker-php-ext-enable xdebug


COPY ./docker-resources/php/* /usr/local/etc/php/conf.d/

RUN a2enmod \
    expires \
    headers \
    proxy \
    proxy_http \
    rewrite \
    ssl

# Extensions PHP
RUN docker-php-ext-configure \
    gd --with-jpeg-dir=/usr/include/

RUN docker-php-ext-install \
    gd \
    pcntl \
    pdo \
    pdo_pgsql \
    pgsql \
    zip

# Installation de l'extension imap
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap


# Paquets PEAR
RUN pear install \
    Mail \
    Mail_Mime \
    Mail_mimeDecode \
    MDB2 \
    MDB2#pgsql

# Répertoire contenant les certificats
RUN mkdir -p /etc/apache2/ssl/


# Répertoire de configuration de S2low
RUN mkdir -p /etc/s2low/ssl/

# Workspace
RUN mkdir -p /data/tdt-workspace/ && \
    chown -R www-data:www-data /data/tdt-workspace/


#Sessions PHP
RUN mkdir -p /var/lib/php/session/ && \
    chown www-data: /var/lib/php/session

# Fichier de trace PHP
RUN touch /tmp/slow.log && \
    chown www-data: /tmp/slow.log

#Mise en ce place du systeme de recuperation des CRL et AC
#TODO voir comment gérer la récupération du validca
ADD ./docker-resources/certificate/recup_crl_v1.1.03.sh /usr/local/bin/recup_crl.sh
RUN chmod +x /usr/local/bin/recup_crl.sh
RUN	/usr/local/bin/recup_crl.sh /etc/s2low/ssl/

# Copie des crontab
COPY ./docker-resources/cron.d/* /etc/cron.d/

# Installation certificat pour récupérer tdt-lib-actes sur gitlab privée...

# Installation de composer
RUN cd /tmp/ && \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php --install-dir=/usr/local/bin && \
    mv /usr/local/bin/composer.phar /usr/local/bin/composer


# Ports
EXPOSE 443 80

# Répertoire de travail
WORKDIR /var/www/s2low/


COPY ./docker-resources/apache/* /etc/apache2/sites-available/
RUN a2ensite s2low-apache-config.conf


COPY ./docker-resources/docker-s2low-entrypoint /usr/local/bin/
RUN chmod a+x /usr/local/bin/docker-s2low-entrypoint


COPY ./docker-resources/supervisord/*.conf /etc/supervisor/conf.d/


COPY ./ /var/www/s2low/

#Composer
RUN composer update
ENV PATH="${PATH}:/var/www/s2low/vendor/bin/"


# Pour libersign
RUN mkdir -p /var/www/parapheur/libersign
ADD https://ressources.libriciel.fr/s2low/libersign_v1_compat.tgz /var/www/parapheur/libersign
RUN cd /var/www/parapheur/libersign && tar xvzf libersign_v1_compat.tgz

RUN ln -s /var/www/parapheur/libersign /var/www/s2low/public.ssl/libersign


ENTRYPOINT ["docker-s2low-entrypoint"]
CMD ["/usr/bin/supervisord"]
