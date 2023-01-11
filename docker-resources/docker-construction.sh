#! /bin/bash

set -e -x

cd /tmp/

# Copie des fichiers de configurations
cp ./docker-resources/php/* /etc/php/8.1/cli/conf.d/
cp ./docker-resources/php/* /etc/php/8.1/apache2/conf.d/
cp ./docker-resources/logrotate.d/*.conf /etc/logrotate.d/
cp ./docker-resources/clamav/clamd.conf /etc/clamav/

cp ./docker-resources/apache/site-available/*.conf /etc/apache2/sites-available/
cp ./docker-resources/apache/conf-available/*.conf /etc/apache2/conf-available/

cp ./docker-resources/supervisord/*.conf /etc/supervisor/conf.d/
cp ./docker-resources/logrotate.d/*.conf /etc/logrotate.d/
cp ./docker-resources/supervisord.conf /etc/supervisor/


# Crond configuration
for CRONFILE in ./docker-resources/cron.d/*
do
  sed -e "s/%USERNAME%/${USERNAME}/g" $CRONFILE > "/etc/cron.d/$(basename $CRONFILE)"
done
chmod 0644 /etc/cron.d/*


# Copie de l'entrypoint
cp ./docker-resources/docker-s2low-entrypoint /usr/local/bin/
chmod a+x /usr/local/bin/docker-s2low-entrypoint

# Ajout du support legacy à openssl
bash docker-resources/certificate/add-legacy-provider-to-openssl-v3.sh

# Répertoire contenant les certificats
# Répertoire de configuration de S2low
# Workspace
mkdir -p /etc/apache2/ssl/
chown -R "${USERNAME}":"${GROUPNAME}" /etc/apache2/ssl/

mkdir -p /etc/s2low/ssl/
chown -R "${USERNAME}":"${GROUPNAME}" /etc/s2low/ssl/

mkdir -p /data/{config,tdt-workspace,log,run,lock,certificate}
mkdir -p /data/certificate/truststore
chown -R "${USERNAME}":"${GROUPNAME}" /data/

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
cd /tmp/

# Module Apache
a2enmod \
    expires \
    headers \
    proxy \
    proxy_http \
    rewrite \
    ssl

a2ensite s2low-apache-config.conf
a2dissite 000-default.conf

sed -e "s/%USERNAME%/$USERNAME/g"  -e "s/%GROUPNAME%/$GROUPNAME/g" ./docker-resources/apache/envvars > /etc/apache2/envvars
mkdir /data/run/apache2
mkdir -p /data/lock/apache2
mkdir -p /data/log/apache2

chown "${USERNAME}": /data/run/apache2
chown "${USERNAME}": /data/lock/apache2
chown "${USERNAME}": /data/log/apache2

usermod -s /bin/bash www-data

# Pour le simulateur...
chown "${USERNAME}":"${GROUPNAME}" /etc/apache2/sites-enabled/
chown "${USERNAME}":"${GROUPNAME}" /var/lib/apache2/site/enabled_by_admin/

# Pour la récup des certif FIXME à déplacer dans /data
chown -R "${USERNAME}":"${GROUPNAME}" /etc/s2low/ssl/
chown -R "${USERNAME}":"${GROUPNAME}" /etc/ssl/certs/

mkdir /var/run/htmlpurifier/
chown -R "${USERNAME}":"${GROUPNAME}" /var/run/htmlpurifier/