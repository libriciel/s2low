#! /bin/bash
set -e

cat <<EOF
account		      default
auth            off
tls             off
tls_starttls    off
host            ${SMTP_SERVER:-mailsec}
port            ${SMTP_PORT:-25}
from		        msmtp@${SITE_HOST_NAME-default.fr}
logfile         /var/log/msmtp.log

EOF
