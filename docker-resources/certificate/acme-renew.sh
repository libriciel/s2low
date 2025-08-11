#!/usr/bin/bash

if [ -f /data/config/acme.env ]; then
    set -a
    source /data/config/acme.env
    set +a
fi

/usr/bin/bash /var/www/s2low/docker-resources/certificate/acme.sh >> /data/log/acme-renew.log 2>&1 && /usr/sbin/apachectl graceful
