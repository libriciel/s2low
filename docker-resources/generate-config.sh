#! /bin/bash
set -e

# Utiliser pour créer un fichier de settings en fonction des variables d'environnement (envoyé par Docker)

cat <<EOF
<?php

define("WEBSITE","${S2LOW_WEBSITE}");
define("WEBSITE_SSL","${S2LOW_WEBSITE_SSL}");
define('DB_HOST', "${POSTGRES_HOST}");
define('DB_USER', "${POSTGRES_USER}");
define('DB_PASSWORD', "${POSTGRES_PASSWORD}");
define('DB_DATABASE', "${POSTGRES_DB}");

define('TRACE_FILE_PATH','/tmp/slow.log');
define('HELIOS_FILES_ROOT', '/data/tdt-workspace/helios/');
define('HELIOS_FILES_UPLOAD_ROOT', '/data/tdt-workspace/helios/sending/');
define('HELIOS_RESPONSES_ROOT', '/data/tdt-workspace/helios/response/');
define('HELIOS_RESPONSES_ERROR_PATH', '/data/tdt-workspace/helios/response_error/');
define('HELIOS_FILES_UPLOAD_TMP', '/data/tdt-workspace/helios/sending-tmp/');
define('HELIOS_COUNTER_FILE',"/data/tdt-workspace/helios/counter.txt");
define('HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH','/data/tdt-workspace/helios/response_tmp/');
define('HELIOS_RESPONSES_ERROR_PATH', '/data/tdt-workspace/helios/response_error/');


define('TIMESTAMPING_CERT', '/etc/apache2/ssl/s2low_timestamp_cert.pem');
define('TIMESTAMPING_PRIV_KEY', '/etc/apache2/ssl/s2low_timestamp_key.pem');
define('TIMESTAMPING_PRIV_KEY_PASS', '/etc/apache2/ssl/tedetis_timestamp_key.pass');

define("RGS_VALIDCA_PATH","/etc/s2low/ssl/validca/");
define("EXTENDED_VALIDCA_PATH","/etc/s2low/ssl/validca/");
define("OPENSSL_PATH","/usr/bin/openssl");


define("HELIOS_FTP_SERVER","${HELIOS_FTP_SERVER}");
define("HELIOS_FTP_PORT","${HELIOS_FTP_PORT}");
define("HELIOS_FTP_PASSIVE_MODE", ${HELIOS_FTP_PASSIVE_MODE});
define("HELIOS_SENDING_DESTINATION","${HELIOS_SENDING_DESTINATION}");
define("HELIOS_FTP_RESPONSE_SERVER_PATH","${HELIOS_FTP_RESPONSE_SERVER_PATH}");
define('HELIOS_FTP_LOGIN',"${HELIOS_FTP_LOGIN}");
define('HELIOS_FTP_PASSWORD',"${HELIOS_FTP_PASSWORD}");

define('ACTES_FILES_UPLOAD_ROOT', '/data/tdt-workspace/actes/uploads/');
define('ACTES_BATCHES_UPLOAD_ROOT', '/data/tdt-workspace/actes/batchs');
define('ACTES_TDT_MAIL_ADDRESS', 'mail@tedetis.org');


EOF

