#! /bin/bash
set -e

# Utiliser pour creer un fichier de settings symfony en fonction des variables d'environnement (envoye par Docker)
#TODO : integrer les variables WEB_HTTP_PORT et WEB_HTTPS_PORT

cat <<EOF
APP_ENV=${APP_ENV:-test}
APP_DEBUG=${APP_DEBUG:-true}

MAILER_DSN="${MAILER_DSN:-smtp://maildev:1025?verify_peer=0}"

ACTES_APPLI_TRIGRAMME=${ACTES_APPLI_TRIGRAMME:-abc}
EOF