#! /bin/bash
set -e

# Utiliser pour creer un fichier de settings en fonction des variables d'environnement (envoye par Docker)
#TODO : integrer les variables WEB_HTTP_PORT et WEB_HTTPS_PORT

cat <<EOF
<?php

define("WEBSITE","http://${S2LOW_WEBSITE:-s2low.docker.libriciel.fr}${WEB_HTTP_PORT/$WEB_HTTP_PORT/:}${WEB_HTTP_PORT:-}/");
define("WEBSITE_SSL","https://${S2LOW_WEBSITE:-s2low.docker.libriciel.fr}${WEB_HTTPS_PORT/$WEB_HTTPS_PORT/:}${WEB_HTTPS_PORT:-}/");

define('EMAIL_ADMIN_TECHNIQUE',"${EMAIL_ADMIN_TECHNIQUE:-tedetis@localhost}");

define('DB_HOST', "${POSTGRES_HOST:-db}");
define('DB_USER', "${POSTGRES_USER:-s2lowuser}");
define('DB_PASSWORD', "${POSTGRES_PASSWORD:-s2lowpassword}");
define('DB_DATABASE', "${POSTGRES_DB:-s2lowdb}");

define('DB_HOST_TEST', "${POSTGRES_HOST_TEST:-localhost}");
define('DB_USER_TEST', "${POSTGRES_USER_TEST:-tedetis}");
define('DB_PASSWORD_TEST', "${POSTGRES_PASSWORD_TEST:-tedetis}");
define('DB_DATABASE_TEST', "${POSTGRES_DATABASE_TEST:-tedetis}");

define("HELIOS_FTP_SERVER","${HELIOS_FTP_SERVER:-ftp}");
define("HELIOS_FTP_PORT","${HELIOS_FTP_PORT:-21}");
define("HELIOS_FTP_PASSIVE_MODE", ${HELIOS_FTP_PASSIVE_MODE:-false});
define("HELIOS_SENDING_DESTINATION","${HELIOS_SENDING_DESTINATION:-/entree/}");
define("HELIOS_FTP_RESPONSE_SERVER_PATH","${HELIOS_FTP_RESPONSE_SERVER_PATH:-/sortie/}");
define('HELIOS_FTP_LOGIN',"${HELIOS_FTP_LOGIN:-helios}");
define('HELIOS_FTP_PASSWORD',"${HELIOS_FTP_PASSWORD:-helios}");

define('ACTES_TDT_MAIL_ADDRESS', "${IMAP_LOGIN:-s2low@s2low.docker.libriciel.fr}");
define('ACTES_IMAP_HOST', "${IMAP_SERVER:-mail}");
define('ACTES_IMAP_LOGIN', "${IMAP_LOGIN:-s2low@s2low.docker.libriciel.fr}");
define('ACTES_IMAP_PASSWORD', "${IMAP_PASS:-password}");

define("OPENSTACK_AUTHENTICATION_URL_V2","${OPENSTACK_AUTHENTICATION_URL_V2}:-https://identity.fr1.cloudwatt.com/v2.0}");
define("OPENSTACK_USERNAME","${OPENSTACK_USERNAME}");
define("OPENSTACK_PASSWORD","${OPENSTACK_PASSWORD}");
define("OPENSTACK_TENANT","${OPENSTACK_TENANT}");
define("OPENSTACK_REGION","${OPENSTACK_REGION}");
define("OPENSTACK_SWIFT_CONTAINER_PREFIX","${OPENSTACK_SWIFT_CONTAINER_PREFIX}");

define("IMAP_SERVER","${IMAP_SERVER}");
define("IMAP_LOGIN","${IMAP_LOGIN}");
define("IMAP_PASS","${IMAP_PASS}");

#A v�rifier
#define('ANTIVIRUS_COMMAND','/usr/bin/clamdscan --fdpass --stream');
define('PADES_VALID_URL', "${PADES_VALID_URL:-http://pades-valid:8080}");
define('PDF_STAMP_URL', "${PDF_STAMP_URL:-http://pdf-stamp:8080}");

define('HELIOS_OCRE_FILE_PATH','/data/tdt-workspace/helios/ocre/');


EOF
