#!/bin/bash

set -e

if [ ! -d /data/certificates/_validca/validca ] ; then
    echo "Récupération des CRL"
    /bin/sh /usr/local/bin/validca.sh
fi
/bin/sh /usr/local/bin/certbot-acme.sh --standalone
/bin/sh /usr/local/bin/self-signed-certificates.sh

exec "$@"