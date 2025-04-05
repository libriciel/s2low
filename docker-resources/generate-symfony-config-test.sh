#! /bin/bash
set -e

# Utiliser pour creer un fichier de settings symfony en fonction des variables d'environnement (envoye par Docker)
#TODO : integrer les variables WEB_HTTP_PORT et WEB_HTTPS_PORT

cat <<EOF

DB_HOST=${POSTGRES_HOST_TEST:-dbtest}
DB_USER=${POSTGRES_USER_TEST:-s2lowusertest}
DB_PASSWORD=${POSTGRES_PASSWORD_TEST:-s2lowpasswordtest}
DB_DATABASE=${POSTGRES_DATABASE_TEST:-s2lowdbtest}

ANTIVIRUS_COMMAND='ls'
EOF