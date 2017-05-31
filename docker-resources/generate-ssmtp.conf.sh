#! /bin/bash
set -e

cat <<EOF

mailhub=${SMTP_SERVER}:${SMTP_PORT}
FromLineOverride=YES


EOF