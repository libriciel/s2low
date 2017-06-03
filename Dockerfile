FROM php:5.5-apache

RUN apt-get update && apt-get install -y \
    libjpeg-dev \
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
    pdo \
    pdo_pgsql \
    pgsql

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



# Installation de composer
RUN cd /tmp/ && \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php --install-dir=/usr/local/bin && \
    mv /usr/local/bin/composer.phar /usr/local/bin/composer

# Installation des dépendances composer
COPY ./composer.* /usr/local/lib/composer/
RUN cd /usr/local/lib/composer && \
    composer install --dev

# Ports
EXPOSE 443 80

# Répertoire de travail
WORKDIR /var/www/s2low/


COPY ./docker-resources/apache/* /etc/apache2/sites-available/
RUN a2ensite s2low-apache-config.conf


COPY ./docker-resources/docker-s2low-entrypoint /usr/local/bin/
RUN chmod a+x /usr/local/bin/docker-s2low-entrypoint


COPY ./docker-resources/s2low-supervisord.conf /etc/supervisor/conf.d/


COPY ./ /var/www/s2low/

# Pour libersign
RUN mkdir -p /var/www/parapheur/libersign
RUN ln -s /var/www/parapheur/libersign /var/www/s2low/public.ssl/libersign


ENTRYPOINT ["docker-s2low-entrypoint"]
CMD ["/usr/bin/supervisord"]
