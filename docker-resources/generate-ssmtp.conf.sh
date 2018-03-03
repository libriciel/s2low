#! /bin/bash
set -e

#TODO mettre les valeurs par défaut
cat <<EOF

mailhub=${SMTP_SERVER}:${SMTP_PORT}
FromLineOverride=YES


EOF
