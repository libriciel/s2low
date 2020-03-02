FROM php:7.2-apache-stretch

#Suppression des repos buster présents dans l'image
RUN rm /etc/apt/sources.list.d/buster.list && rm /etc/apt/preferences.d/argon2-buster

RUN apt-get update && \
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
        ssmtp \
        sudo \
        supervisor \
        xmlsec1 \
        xmlstarlet \
        wget \
        zip \
        netcat \
    && rm -r /var/lib/apt/lists/*

# Suppression de php5-imagick => A priori, ca ne servait que pour le tampon (qui a disparu) et l'install est complexe

# Installation de certbot
RUN echo 'deb http://ftp.debian.org/debian stretch-backports main' >  /etc/apt/sources.list.d/stretch.backport.list && \
    apt-get update && \
    apt-get install -y -t stretch-backports \
        certbot \
        python-certbot-apache \
    && rm -r /var/lib/apt/lists/*


# Gestion des locales
RUN sed -i -e 's/# fr_FR.UTF-8 UTF-8/fr_FR.UTF-8 UTF-8/' /etc/locale.gen && \
    echo 'LANG="fr_FR.UTF-8"'>/etc/default/locale&& \
    set -eux && dpkg-reconfigure --frontend=noninteractive locales && \
    update-locale LANG=fr_FR.UTF-8 && \
    echo "Europe/Paris" > /etc/timezone &&\
    set -eux && dpkg-reconfigure -f noninteractive tzdata


# Paquet PECL
RUN pecl install \
        redis \
        xdebug && \
    docker-php-ext-enable \
        redis \
        xdebug

# Extensions PHP
RUN docker-php-ext-configure gd --with-jpeg-dir=/usr/include/ && \
    docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
    docker-php-ext-install \
        gd \
        imap \
        pcntl \
        pdo \
        pdo_pgsql \
        pgsql \
        zip




# Paquets PEAR
#RUN pear install \
#       Mail \
#        Mail_Mime \
#        Mail_mimeDecode \
#        MDB2 \
#        MDB2#pgsql

#Suite site pear down suite à attaque
RUN cd /tmp && \
    wget \
            https://ressources.libriciel.fr/deploiement/m/mail-v1.4.1.tar.gz \
            https://ressources.libriciel.fr/deploiement/m/mail_mime-1.10.2.tar.gz \
            && \
    pear install \
        mail-v1.4.1.tar.gz \
        mail_mime-1.10.2.tar.gz


# Installation de composer
RUN cd /tmp/ && \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php --install-dir=/usr/local/bin && \
    mv /usr/local/bin/composer.phar /usr/local/bin/composer

# Module Apache
RUN a2enmod \
        expires \
        headers \
        proxy \
        proxy_http \
        rewrite \
        ssl

# Copie des fichiers de configurations
COPY ./docker-resources/php/* /usr/local/etc/php/conf.d/
COPY ./docker-resources/logrotate.d/*.conf /etc/logrotate.d/
COPY ./docker-resources/clamav/clamd.conf /etc/clamav/
COPY ./docker-resources/cron.d/* /etc/cron.d/
COPY ./docker-resources/apache/* /etc/apache2/sites-available/
COPY ./docker-resources/supervisord/*.conf /etc/supervisor/conf.d/
COPY ./docker-resources/logrotate.d/*.conf /etc/logrotate.d/

# Copie de l'entrypoint
COPY ./docker-resources/docker-s2low-entrypoint /usr/local/bin/
RUN chmod a+x /usr/local/bin/docker-s2low-entrypoint

# Répertoire contenant les certificats
# Répertoire de configuration de S2low
# Workspace
RUN mkdir -p /etc/apache2/ssl/ && \
    mkdir -p /etc/s2low/ssl/ && \
    mkdir -p /data/tdt-workspace/ && \
    chown -R www-data:www-data /data/tdt-workspace/


#Mise en ce place du systeme de recuperation des CRL et AC
#TODO voir comment gérer la récupération du validca
ADD ./docker-resources/certificate/recup_crl_v1.1.03.sh /usr/local/bin/recup_crl.sh
ADD ./docker-resources/certificate/recup_ac_ministere.sh /usr/local/bin/recup_ac_ministere.sh
RUN chmod +x /usr/local/bin/recup_crl.sh
RUN chmod +x /usr/local/bin/recup_ac_ministere.sh

RUN	/usr/local/bin/recup_crl.sh /etc/s2low/ssl/

# Pour libersign
RUN mkdir -p /var/www/parapheur/libersign
ADD https://ressources.libriciel.fr/s2low/libersign_v1_compat.tgz /var/www/parapheur/libersign
RUN cd /var/www/parapheur/libersign && tar xvzf libersign_v1_compat.tgz

# Ports
EXPOSE 443 80

# Répertoire de travail
WORKDIR /var/www/s2low/


#TODO : mettre des VOLUME pour le workspace ?
#TODO : mettre un VOLUME pour les certificats letsencrypt ?


COPY ./ /var/www/s2low/

#Ajoute droit d'execution sur script monitor ipsec
RUN chmod +x /var/www/s2low/script/divers/ipsec-monitor.sh

RUN ln -s /var/www/parapheur/libersign /var/www/s2low/public.ssl/libersign

RUN a2ensite s2low-apache-config.conf

#Composer
# https://adamcod.es/2013/03/07/composer-install-vs-composer-update.html
RUN composer install
ENV PATH="${PATH}:/var/www/s2low/vendor/bin/"


ENTRYPOINT ["docker-s2low-entrypoint"]
CMD ["/usr/bin/supervisord","-c","/etc/supervisor/supervisord.conf"]
