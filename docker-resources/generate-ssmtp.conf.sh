#! /bin/bash
set -e

cat <<EOF

mailhub=${SMTP_SERVER:-mailsec}:${SMTP_PORT:-25}
FromLineOverride=YES


EOF
