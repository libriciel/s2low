#! /bin/bash
set -e

# Utiliser pour créer un fichier de settings en fonction des variables d'environnement (envoyé par Docker)

cat <<EOF
<?php

define("WEBSITE","${S2LOW_WEBSITE:-http://localhost/}");
define("WEBSITE_SSL","${S2LOW_WEBSITE_SSL:-https://localhost}");
define('DB_HOST', "${POSTGRES_HOST:-localhost}");
define('DB_USER', "${POSTGRES_USER:-tedetis}");
define('DB_PASSWORD', "${POSTGRES_PASSWORD:-tedetis}");
define('DB_DATABASE', "${POSTGRES_DB:-tedetis}");


define('DB_HOST_TEST', "${POSTGRES_HOST_TEST:-localhost}");
define('DB_USER_TEST', "${POSTGRES_USER_TEST:-tedetis}");
define('DB_PASSWORD_TEST', "${POSTGRES_PASSWORD_TEST:-tedetis}");
define('DB_DATABASE_TEST', "${POSTGRES_DATABASE_TEST:-tedetis}");


define('TRACE_FILE_PATH','/tmp/slow.log');
define('HELIOS_FILES_ROOT', '/data/tdt-workspace/helios/');
define('HELIOS_FILES_UPLOAD_ROOT', '/data/tdt-workspace/helios/sending/');
define('HELIOS_RESPONSES_ROOT', '/data/tdt-workspace/helios/response/');
define('HELIOS_RESPONSES_ERROR_PATH', '/data/tdt-workspace/helios/response_error/');
define('HELIOS_FILES_UPLOAD_TMP', '/data/tdt-workspace/helios/sending-tmp/');
define('HELIOS_COUNTER_FILE',"/data/tdt-workspace/helios/counter.txt");
define('HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH','/data/tdt-workspace/helios/response_tmp/');

define('MAIL_FILES_UPLOAD_ROOT','/data/tdt-workspace/mail/');


define('TIMESTAMPING_CERT', '/etc/apache2/ssl/s2low_timestamp_cert.pem');
define('TIMESTAMPING_PRIV_KEY', '/etc/apache2/ssl/s2low_timestamp_key.pem');
define('TIMESTAMPING_PRIV_KEY_PASS', '/etc/apache2/ssl/tedetis_timestamp_key.pass');

define("RGS_VALIDCA_PATH","/etc/s2low/ssl/validca/");
define("EXTENDED_VALIDCA_PATH","/etc/s2low/ssl/validca/");
define("OPENSSL_PATH","/usr/bin/openssl");


define("HELIOS_FTP_SERVER","${HELIOS_FTP_SERVER:-localhost}");
define("HELIOS_FTP_PORT","${HELIOS_FTP_PORT:-21}");
define("HELIOS_FTP_PASSIVE_MODE", ${HELIOS_FTP_PASSIVE_MODE:-false});
define("HELIOS_SENDING_DESTINATION","${HELIOS_SENDING_DESTINATION:-/entree}");
define("HELIOS_FTP_RESPONSE_SERVER_PATH","${HELIOS_FTP_RESPONSE_SERVER_PATH:-/sortie}");
define('HELIOS_FTP_LOGIN',"${HELIOS_FTP_LOGIN}");
define('HELIOS_FTP_PASSWORD',"${HELIOS_FTP_PASSWORD}");

define('ACTES_FILES_UPLOAD_ROOT', '/data/tdt-workspace/actes/uploads/');
define('ACTES_BATCHES_UPLOAD_ROOT', '/data/tdt-workspace/actes/batchs');
define('ACTES_RESPONSE_TMP_LOCAL_PATH', '/data/tdt-workspace/actes/response_tmp');
define('ACTES_RESPONSE_ERROR_PATH', '/data/tdt-workspace/actes/response_error');

define('ACTES_TDT_MAIL_ADDRESS', 'mail@tedetis.org');



define('LIBERSIGN_INSTALLER',"${LIBERSIGN_INSTALLER}");
define("LIBERSIGN_URL","${S2LOW_WEBSITE_SSL}/libersign/");
define("LIBERSIGN_EXTENSION_UPDATE_URL","${S2LOW_WEBSITE_SSL}/libersign/");


define("OPENSTACK_AUTHENTICATION_URL_V2","${OPENSTACK_AUTHENTICATION_URL_V2}");
define("OPENSTACK_USERNAME","${OPENSTACK_USERNAME}");
define("OPENSTACK_PASSWORD","${OPENSTACK_PASSWORD}");
define("OPENSTACK_TENANT","${OPENSTACK_TENANT}");
define("OPENSTACK_REGION","${OPENSTACK_REGION}");
define("OPENSTACK_SWIFT_CONTAINER_PREFIX","${OPENSTACK_SWIFT_CONTAINER_PREFIX}");

define("IMAP_SERVER","${IMAP_SERVER}");
define("IMAP_LOGIN","${IMAP_LOGIN}");
define("IMAP_PASS","${IMAP_PASS}");


EOF

