#! /bin/bash
set -e

cat <<EOF
account		      default
auth            off
tls             off
tls_starttls    off
host            ${SMTP_SERVER:-mailsec}
port            ${SMTP_PORT:-25}
from		        default@${SITE_HOST_NAME-default.fr}
tls_trust_file  /etc/ssl/certs/ca-certificates.crt
logfile         /var/log/msmtp.log
FromLineOverride=YES

EOF
