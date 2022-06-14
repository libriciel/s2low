#! /bin/bash

set -e -x

cd /tmp/

# Copie des fichiers de configurations
cp ./docker-resources/php/* /etc/php/8.1/cli/conf.d/
cp ./docker-resources/php/* /etc/php/8.1/apache2/conf.d/
cp ./docker-resources/logrotate.d/*.conf /etc/logrotate.d/
cp ./docker-resources/clamav/clamd.conf /etc/clamav/
cp ./docker-resources/cron.d/* /etc/cron.d/
cp ./docker-resources/apache/* /etc/apache2/sites-available/
cp ./docker-resources/supervisord/*.conf /etc/supervisor/conf.d/
cp ./docker-resources/logrotate.d/*.conf /etc/logrotate.d/

# Copie de l'entrypoint
cp ./docker-resources/docker-s2low-entrypoint /usr/local/bin/
chmod a+x /usr/local/bin/docker-s2low-entrypoint

# Ajout du support legacy à openssl
bash docker-resources/certificate/add-legacy-provider-to-openssl-v3.sh

# Répertoire contenant les certificats
# Répertoire de configuration de S2low
# Workspace
mkdir -p /etc/apache2/ssl/
mkdir -p /etc/s2low/ssl/
mkdir -p /data/tdt-workspace/
chown -R www-data:www-data /data/tdt-workspace/


#Mise en ce place du systeme de recuperation des CRL et AC
#TODO voir comment gérer la récupération du validca
cp ./docker-resources/certificate/recup_ac_ministere.sh /usr/local/bin/recup_ac_ministere.sh
cp ./docker-resources/certificate/wait-for-certificates.sh /usr/local/bin/wait-for-certificates.sh
chmod +x /usr/local/bin/recup_ac_ministere.sh
chmod +x /usr/local/bin/wait-for-certificates.sh

/usr/bin/curl -s https://validca.libriciel.fr/retrieve-validca.sh | /bin/bash -s /etc/s2low/ssl


# Pour libersign
mkdir -p /var/www/parapheur/libersign
cd /var/www/parapheur/libersign/
wget https://ressources.libriciel.fr/s2low/libersign_v1_compat.tgz
tar xvzf libersign_v1_compat.tgz

# Module Apache
a2enmod \
    expires \
    headers \
    proxy \
    proxy_http \
    rewrite \
    ssl

a2ensite s2low-apache-config.conf
