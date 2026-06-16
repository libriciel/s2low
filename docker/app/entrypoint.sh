#!/bin/bash

set -e

CRONTAB_FILE=/data/config/crontab
> "$CRONTAB_FILE"
for file in "/app/docker/app/cron.d"/*; do
  cat "$file" >> "$CRONTAB_FILE"
  echo "" >> "$CRONTAB_FILE"
done

/bin/bash /app/docker/app/generate-config.sh > /data/config/DockerSettings.php
/bin/sh /usr/local/bin/create-directory-structure.sh

#FIXME: file is needed during bootEnv
touch /data/config/.env

cp /etc/ssl/certs/* /data/certificates/actes_truststore/

if [ -z "$DONT_RETRIEVE_VALIDCA" ] ; then
    if [ ! -d /data/certificates/_validca/validca ] ; then
        echo "Récupération des CRL"
        /usr/local/bin/validca.sh
    fi

  # Ajout des ACs du ministère
  /usr/local/bin/recup_ac_ministere.sh /data/certificates/ /data/certificates/actes_truststore/
fi

if [ -z "$DONT_INIT_DATABASE" ] ; then
  until nc -z -v -w30 "${DATABASE_HOST}" 5432
  do
    echo 'Waiting for database connection...'
    sleep 5
  done

  /app/bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

exec "$@"
