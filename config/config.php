<?php

/* Quel bordel, des fois on appel config.php, des fois init.php. Je rajoute ca la a cause de Monolog */

use S2lowLegacy\Class\Helpers;

require_once __DIR__ . "/../vendor/autoload.php";

require_once(__DIR__ . "/LoadLocalSettings.php");


ini_set("error_reporting", E_ALL);

date_default_timezone_set("Europe/Paris");

//Attention, changement de la locale LC_TIME : ne pas la redéfinir dans le fichier LocalSettings !!!
//Cette locale est cohérente avec le code de S2low
setlocale(LC_TIME, "fr_FR.UTF-8");

if (! defined("OPERATEUR_DE_TELETRANSMISSION")) {
    define("OPERATEUR_DE_TELETRANSMISSION", "Libriciel SCOP");
}


if (!defined("EMAIL_ADMIN")) {
        define("EMAIL_ADMIN", "noreply@s2low.docker.libriciel.fr");
}

// Adresse email sur laquelle seront reçu les alertes critiques du système nécessitant une intervention immédiate
// Possibilité de mettre plusieurs emails séparés par des virgules
if (!defined("EMAIL_ADMIN_TECHNIQUE")) {
    define("EMAIL_ADMIN_TECHNIQUE", "noreply@libriciel.net");
}

if (!defined("WEBSITE_TITLE")) {
        define('WEBSITE_TITLE', "Tiers de téléransmission multiprotocoles");
}

if (!defined('WEBSITE')) {
        define('WEBSITE', "http://s2low.docker.libriciel.fr/");
}

if (!defined('WEBSITE_SSL')) {
        define('WEBSITE_SSL', "https://s2low.docker.libriciel.fr/");
}

if (!defined('WEBSITE_MAIL')) {
    define('WEBSITE_MAIL', "https://mail.s2low.docker.libriciel.fr/");
}

if (!defined('WEBMASTER')) {
        define('WEBMASTER', "mailto:webmaster@s2low.docker.libriciel.fr");
}

// Adresse du From des emails émis
if (!defined('TDT_FROM_EMAIL')) {
        define('TDT_FROM_EMAIL', "Tiers de télétransmission <s2low@s2low.docker.libriciel.fr>");
}

// Image utilisee pour le tampon des actes
if (!defined('IMAGE_FOR_STAMP')) {
        define('IMAGE_FOR_STAMP', __DIR__ . "/../public.ssl/custom/images/s2low-stamp.png");
}

if (!defined('LOG_FILE')) {
    define('LOG_FILE', '/data/log/s2low.log');
}

// Constantes générales
if (!defined('TRACE_FILE_PATH')) {
    //TODO supprimer la classe Trace et utiliser le Logger principal
    define('TRACE_FILE_PATH', '/data/log/slow.log');
}

if (!defined('LOG_LEVEL')) {
    define('LOG_LEVEL', Monolog\Logger::INFO);
}

if (!defined('CONVERT_API_LOGINS_FROM_ISO')) {
    define('CONVERT_API_LOGINS_FROM_ISO', false);
}


// Paramètres base de données
if (!defined('DB_HOST')) {
        define('DB_HOST', "db");
}

if (!defined('DB_USER')) {
        define('DB_USER', "s2lowuser");
}

if (!defined('DB_PASSWORD')) {
        define('DB_PASSWORD', "s2lowpassword");
}

if (!defined('DB_DATABASE')) {
    define('DB_DATABASE', "s2lowdb");
}

if (!defined("BEANSTAKLD_SERVER")) {
    define("BEANSTAKLD_SERVER", "beanstalkd");
}

if (!defined("BEANSTAKLD_PORT")) {
    define("BEANSTAKLD_PORT", "11300");
}

if (!defined("REDIS_SERVER")) {
    define("REDIS_SERVER", "redis");
}

if (!defined("REDIS_PORT")) {
    define("REDIS_PORT", 6379);
}

//D?finition de la connexion à la base de données pour les tests unitaires et les tests de validation
if (!defined('DB_HOST_TEST')) {
    define('DB_HOST_TEST', "dbtest");
}

if (!defined('DB_USER_TEST')) {
    define('DB_USER_TEST', "s2lowusertest");
}

if (!defined('DB_PASSWORD_TEST')) {
    define('DB_PASSWORD_TEST', "s2lowpasswordtest");
}

if (!defined('DB_DATABASE_TEST')) {
    define('DB_DATABASE_TEST', "s2lowdbtest");
}

// Nombre d'élément affichés par défaut par page dans les listes
if (!defined('DEFAULT_ITEMS_PER_PAGE')) {
        define('DEFAULT_ITEMS_PER_PAGE', 10);
}

//Chemin vers openssl version > 1.0.0a
if (!defined("OPENSSL_PATH")) {
        define("OPENSSL_PATH", "/usr/bin/openssl");
}


if (!defined('ANTIVIRUS_COMMAND')) {
        define('ANTIVIRUS_COMMAND', '/usr/bin/clamdscan');
}

if (!defined('TEDETIS_TMP_PATH')) {
        define('TEDETIS_TMP_PATH', '/tmp/');
}

//Paramètre outils de signature XML
if (!defined("XMLSEC1_PATH")) {
    define("XMLSEC1_PATH", "/usr/bin/xmlsec1");
}

if (! defined("ONLY_USE_VALIDCARGS")) {
    define("ONLY_USE_VALIDCARGS", false);
}

//Emplacement d'un répertoire contenant le "hasher" des autorités de certification RGS
//voir "man c_rehash" pour le "hasher"
//Ce répertoire ne doit contenir que des certificats RGS et sert à signer et télétransmettre des flux Actes
if (! defined("RGS_VALIDCA_PATH")) {
    $pathToRgsValidca = "/data/certificates/_validca/validca/";
    if (ONLY_USE_VALIDCARGS) {
        $pathToRgsValidca = "/data/certificates/_validca/validcargs/";
    }
    define("RGS_VALIDCA_PATH", $pathToRgsValidca);
}

//Emplacement des certificats permettant la connexion à la plateforme ainsi que la signature et la télétransmission
//des flux PES
if (! defined("EXTENDED_VALIDCA_PATH")) {
    $pathToRgsValidca = "/data/certificates/_validca/validca/";
    if (ONLY_USE_VALIDCARGS) {
        $pathToRgsValidca = "/data/certificates/_validca/validcargs/";
    }
    define("EXTENDED_VALIDCA_PATH", $pathToRgsValidca);
}

//Emplacement où se trouve les AC de /etc/ssl/certs et celles du Ministere
if (! defined("TRUSTSTORE_PATH")) {
    define("TRUSTSTORE_PATH", "/data/certificates/actes_truststore/");
}

//Permet de définir le nombre de mois pendant lequelle un enregistrement est gardé dans la table logs avant
//d'être déposé dans la table logs_historique
if (! defined("KEEP_NB_MONTHS_IN_LOGS")) {
    define("KEEP_NB_MONTHS_IN_LOGS", 6);
}

if (!defined("WORKSPACE_DIRECTORY")) {
    define("WORKSPACE_DIRECTORY", "/data/tdt-workspace/");
}
if (!defined("EXPORT_LOGS_DIRECTORY")) {
    define("EXPORT_LOGS_DIRECTORY", WORKSPACE_DIRECTORY . "logs-export/");
}


///////////////////////////////////
///// Paramètres module Actes /////
///////////////////////////////////

if (!defined("ACTES_MINISTERE_ACRONYME")) {
    //MISILL, MIAT, MIOCT, puis MIOCTI entre 2002 et 2012
    //MI - Ministère de l'intérieur - depuis le 16/05/2012
    define('ACTES_MINISTERE_ACRONYME', 'MI');
}

if (!defined('ACTES_MINISTERE_URL')) {
    define('ACTES_MINISTERE_URL', 'http://simulateur/Simulateur/actesPost');
}

if (!defined('ACTES_MINISTERE_AUTHENTICATION')) {
    #One off NONE, POST or BASIC
    define('ACTES_MINISTERE_AUTHENTICATION', 'NONE');
}

if (!defined('ACTES_MINISTERE_LOGIN')) {
    define('ACTES_MINISTERE_LOGIN', '');
}

if (!defined('ACTES_MINISTERE_PASSWORD')) {
    define('ACTES_MINISTERE_PASSWORD', '');
}

/* Il s'agit du certificat du client ! */
if (!defined('ACTES_MINISTERE_CERTIFICATE')) {
    define('ACTES_MINISTERE_CERTIFICATE', '');
}

if (!defined('ACTES_MINISTERE_CERTIFICATE_KEY')) {
    define('ACTES_MINISTERE_CERTIFICATE_KEY', '');
}

if (!defined('ACTES_MINISTERE_CERTIFICATE_KEY_PASS')) {
    define('ACTES_MINISTERE_CERTIFICATE_KEY_PASS', '');
}

if (!defined('ACTES_MINISTERE_SERVER_CERTIFICATE_PATH')) {
    define('ACTES_MINISTERE_SERVER_CERTIFICATE_PATH', '');
}

if (!defined('ACTES_MINISTERE_ADAPT_PROTOCOL')) {
    define('ACTES_MINISTERE_ADAPT_PROTOCOL', false);
}

if (!defined('MAILER_DSN')) {
    define('MAILER_DSN', 'smtp://maildev:1025?verify_peer=0');
}

if (!defined('ACTES_IMAP_HOST')) {
    define('ACTES_IMAP_HOST', 'mail');
}

if (!defined('ACTES_IMAP_PORT')) {
    define('ACTES_IMAP_PORT', '143');
}

if (!defined('ACTES_IMAP_LOGIN')) {
    define('ACTES_IMAP_LOGIN', 's2low@s2low.docker.libriciel.fr');
}

if (!defined('ACTES_IMAP_PASSWORD')) {
    define('ACTES_IMAP_PASSWORD', 'password');
}

if (!defined('ACTES_IMAP_OPTIONS')) {
    // Si la valeur par défaut ne fonctionne pas, on peut essayer : /imap/ssl/novalidate-cert
    define('ACTES_IMAP_OPTIONS', '/imap/notls/novalidate-cert');
}

// Nom de l'application vis à vis du MIAT
if (!defined('ACTES_APPLI_NAME')) {
        define('ACTES_APPLI_NAME', 'TACT');
}

// Trigramme de l'application pour la génération des noms d'archive .tar.gz
if (!defined('ACTES_APPLI_TRIGRAMME')) {
        define('ACTES_APPLI_TRIGRAMME', 'abc');
}

if (!defined('ACTES_APPLI_QUADRIGRAMME')) {
    define('ACTES_APPLI_QUADRIGRAMME', 'TACT');
}


// Taille maximum autorisée des archives (en octets)
if (!defined('ACTES_ARCHIVE_MAX_SIZE')) {
        define('ACTES_ARCHIVE_MAX_SIZE', 150 * 1024 * 1024);
}

// Taille maximum pour les envois de fichiers par lot
if (!defined('ACTES_MAX_BATCH_UPLOAD_SIZE')) {
        define('ACTES_MAX_BATCH_UPLOAD_SIZE', 150 * 1024 * 1024);
}

// Adresse életronique du TdT pour le retour des messages du MIAT
if (!defined('ACTES_TDT_MAIL_ADDRESS')) {
        define('ACTES_TDT_MAIL_ADDRESS', 's2low@s2low.docker.libriciel.fr');
}

// Répertoire de stockage des fichiers envoyés par les utilisateurs (archives .tar.gz)
if (!defined('ACTES_FILES_UPLOAD_ROOT')) {
        define('ACTES_FILES_UPLOAD_ROOT', WORKSPACE_DIRECTORY . 'actes/uploads');
}

// Répertoire de stockage des vieux fichiers envoyés par les utilisateurs (archives .tar.gz) sans transaction associée
if (!defined('ACTES_ENVELOPPE_SANSTRANSACTION')) {
    define('ACTES_ENVELOPPE_SANSTRANSACTION', WORKSPACE_DIRECTORY . 'actes/sans_transaction');
}

// Répertoire de stockage des fichiers constituant les lots
if (!defined('ACTES_BATCHES_UPLOAD_ROOT')) {
        define('ACTES_BATCHES_UPLOAD_ROOT', WORKSPACE_DIRECTORY . 'actes/batchs');
}

// Répertoire temporaire de stockage des réponses du ministère par mail
if (!defined('ACTES_RESPONSE_TMP_LOCAL_PATH')) {
    define('ACTES_RESPONSE_TMP_LOCAL_PATH', WORKSPACE_DIRECTORY . 'actes/response_tmp');
}

// Répertoire temporaire de stockage des réponses en erreur du ministère
if (!defined('ACTES_RESPONSE_ERROR_PATH')) {
    define('ACTES_RESPONSE_ERROR_PATH', WORKSPACE_DIRECTORY . 'actes/response_error');
}


// Liste des adresses de destinataires des notification commune écoutes les collectivités de l'instance
if (!defined('ACTES_COMMON_BROADCAST_EMAILS')) {
    define('ACTES_COMMON_BROADCAST_EMAILS', 'defaut@s2low.docker.libriciel.fr');
}

// Restreindre ou non plusieurs demandes de classification par jour par collectivité (1 par jour si restreint)
if (!defined('ACTES_RESTRICT_CLASSIF_REQUEST_FREQUENCY')) {
    define('ACTES_RESTRICT_CLASSIF_REQUEST_FREQUENCY', false);
}

// Message indiquant que le plugin d upload du traitement par lot est en mode dégradé
if (!defined('ACTES_BATCH_UPLOAD_PLUGIN_FALLBACK_MESSAGE')) {
    define('ACTES_BATCH_UPLOAD_PLUGIN_FALLBACK_MESSAGE', "La version de votre navigateur ne permet pas d'utiliser les fonctionnalités de sélection multiple de fichiers de manière optimum.<br />Vous devez choisir vos fichiers un par un.");
}


//Permet sur un site de formation ou de démonstration de ne pas attendre la durée légale de deux mois avant de valider une transaction ACTE.
if (!defined('ACTES_ALWAYS_CAN_VALIDATE')) {
    define('ACTES_ALWAYS_CAN_VALIDATE', false);
}

//Permet d'utiliser le modèle de bordereau à l'ancienne
if (!defined("USE_LEGACY_BORDEREAU_MODEL")) {
    define("USE_LEGACY_BORDEREAU_MODEL", true);
}


////////////////////////////////////
///// Paramètres module Helios /////
////////////////////////////////////
if (!defined('HELIOS_FILES_ROOT')) {
    define('HELIOS_FILES_ROOT', WORKSPACE_DIRECTORY . 'helios/');
}

// Répertoire de stockage des fichiers envoyés par les utilisateurs
if (!defined('HELIOS_FILES_UPLOAD_ROOT')) {
    define('HELIOS_FILES_UPLOAD_ROOT', HELIOS_FILES_ROOT . 'sending/');
}
// Repertoire de stockage des reponses
if (!defined('HELIOS_RESPONSES_ROOT')) {
    define('HELIOS_RESPONSES_ROOT', HELIOS_FILES_ROOT . 'response/');
}

// Repertoire de stockage des reponses en erreur
if (!defined('HELIOS_RESPONSES_ERROR_PATH')) {
    define('HELIOS_RESPONSES_ERROR_PATH', HELIOS_FILES_ROOT . 'response_error/');
}


// Repertoire de stockage des fichiers temporaires à envoyer au FTP
if (!defined('HELIOS_FILES_UPLOAD_TMP')) {
    define('HELIOS_FILES_UPLOAD_TMP', HELIOS_FILES_ROOT . 'sending-tmp/');
}

if (!defined('HELIOS_COUNTER_FILE')) {
    define('HELIOS_COUNTER_FILE', HELIOS_FILES_ROOT . "counter.txt");
}

if (!defined('HELIOS_USE_PASSTRANS_AS_DEFAULT')) {
    define('HELIOS_USE_PASSTRANS_AS_DEFAULT', false);
}

if (!defined('HELIOS_PESALLER_SANSTRANSACTION')) {
    define('HELIOS_PESALLER_SANSTRANSACTION', HELIOS_FILES_ROOT . "orphelins/");
}

if (!defined("HELIOS_ZIP_BEFORE_SEND")) {
    define("HELIOS_ZIP_BEFORE_SEND", false);
}

if (!defined('HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH')) {
    define('HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH', HELIOS_FILES_ROOT . 'response_tmp/');
}

if (!defined('HELIOS_UPSTART_TOUCH_FILE')) {
    define('HELIOS_UPSTART_TOUCH_FILE', '/tmp/helios-upstart');
}

if (!defined('HELIOS_MAX_UPLOAD_SIZE')) {
    define('HELIOS_MAX_UPLOAD_SIZE', 128 * 1024 * 1024);
}

if (!defined('HELIOS_GENERATED_FILE_PERMISSION')) {
    define('HELIOS_GENERATED_FILE_PERMISSION', '0644');
}

if (!defined("HELIOS_OCRE_FILE_PATH")) {
    define("HELIOS_OCRE_FILE_PATH", HELIOS_FILES_ROOT . "ocre/");
}

if (!defined("HELIOS_OCRE_EXPORT_URL")) {
    define("HELIOS_OCRE_EXPORT_URL", "http://localhost/phpstorm/pastell-ocre/script/receive-ocre.php");
}

if (!defined("HELIOS_OCRE_PASSWORD")) {
    define("HELIOS_OCRE_PASSWORD", "changme");
}

if (! defined("HELIOS_DO_NOT_VERIFY_NOM_FIC_UNICITY")) {
    //Permet de faire sauter la vérication de l'unicité du NomFic dans les PES_Aller
    //Il faut mettre cette constante à faux et explicitement coché une case sur l'autorité sur la console
    define("HELIOS_DO_NOT_VERIFY_NOM_FIC_UNICITY", false);
}

///////////////////////////////////
///// Paramètres module Mail  /////
///////////////////////////////////
// Répertoire de stockage des fichiers envoyés par les utilisateurs
if (!defined('MAIL_FILES_UPLOAD_ROOT')) {
    define('MAIL_FILES_UPLOAD_ROOT', WORKSPACE_DIRECTORY . 'mail/');
}

if (!defined('MAIL_FILES_WITHOUT_TRANSAC_DIR')) {
    define('MAIL_FILES_WITHOUT_TRANSAC_DIR', MAIL_FILES_UPLOAD_ROOT . 'sans_transaction/');
}

if (!defined('MAIL_TEDETIS_FROM')) {
    define('MAIL_TEDETIS_FROM', 's2low-mailsec@s2low.docker.libriciel.fr');
}

if (!defined('MAIL_SECURE_DESCRIPTION')) {
    define('MAIL_SECURE_DESCRIPTION', "ne-pas-repondre");
}

if (!defined('MAIL_MESSAGE')) {
    define('MAIL_MESSAGE', "Vous avez reçu un courrier recommandé par S2LOW");
}

if (!defined('MAIL_TEXT')) {
    define('MAIL_TEXT', "Bonjour,

Un courrier recommandé avec d’éventuelles pièces jointes vous a été transmis par la plateforme sécurisée de l’ADULLACT. Nous vous proposons de cliquer sur le lien suivant pour accéder au courrier recommandé et confirmer la réception : accés au courrier recommandé.");
}

if (!defined('USE_LEGACY_SECURE_MAIL_FIELDS')) {
    define('USE_LEGACY_SECURE_MAIL_FIELDS', false);
}

if (! defined("TESTING_ENVIRONNEMENT")) {
    define("TESTING_ENVIRONNEMENT", false);
}

if (! defined("USE_PROD_NOTIFICATIONS")) {
    define("USE_PROD_NOTIFICATIONS", false);
}

## Configuration OpenStack (facultative)

if (! defined("OPENSTACK_ENABLE")) {
    define("OPENSTACK_ENABLE", false);
}

if (! defined("OPENSTACK_AUTHENTICATION_URL_V3")) {
    define("OPENSTACK_AUTHENTICATION_URL_V3", 'https://auth.cloud.ovh.net/v3');
}

if (! defined("OPENSTACK_USERNAME")) {
    define("OPENSTACK_USERNAME", "");
}

if (! defined("OPENSTACK_PASSWORD")) {
    define("OPENSTACK_PASSWORD", "");
}

if (! defined("OPENSTACK_TENANT")) {
    define("OPENSTACK_TENANT", "0750189044_S2LOWDEV");
}

if (! defined("OPENSTACK_REGION")) {
    define("OPENSTACK_REGION", "fr1");
}

if (! defined("OPENSTACK_SWIFT_CONTAINER_PREFIX")) {
    define("OPENSTACK_SWIFT_CONTAINER_PREFIX", "s2low_dev_");
}

/** OpenStack pour Actes (si différent) */

if (! defined("ACTES_OPENSTACK_AUTHENTICATION_URL_V3")) {
    define("ACTES_OPENSTACK_AUTHENTICATION_URL_V3", OPENSTACK_AUTHENTICATION_URL_V3);
}

if (! defined("ACTES_OPENSTACK_USERNAME")) {
    define("ACTES_OPENSTACK_USERNAME", OPENSTACK_USERNAME);
}

if (! defined("ACTES_OPENSTACK_PASSWORD")) {
    define("ACTES_OPENSTACK_PASSWORD", OPENSTACK_PASSWORD);
}

if (! defined("ACTES_OPENSTACK_TENANT")) {
    define("ACTES_OPENSTACK_TENANT", OPENSTACK_TENANT);
}

if (! defined("ACTES_OPENSTACK_REGION")) {
    define("ACTES_OPENSTACK_REGION", OPENSTACK_REGION);
}

if (! defined("ACTES_OPENSTACK_SWIFT_CONTAINER_PREFIX")) {
    define("ACTES_OPENSTACK_SWIFT_CONTAINER_PREFIX", OPENSTACK_SWIFT_CONTAINER_PREFIX);
}

/** OpenStack pour Helios (PES ALLER) (si différent) */

if (! defined("HELIOS_OPENSTACK_AUTHENTICATION_URL_V3")) {
    define("HELIOS_OPENSTACK_AUTHENTICATION_URL_V3", OPENSTACK_AUTHENTICATION_URL_V3);
}

if (! defined("HELIOS_OPENSTACK_USERNAME")) {
    define("HELIOS_OPENSTACK_USERNAME", OPENSTACK_USERNAME);
}

if (! defined("HELIOS_OPENSTACK_PASSWORD")) {
    define("HELIOS_OPENSTACK_PASSWORD", OPENSTACK_PASSWORD);
}

if (! defined("HELIOS_OPENSTACK_TENANT")) {
    define("HELIOS_OPENSTACK_TENANT", OPENSTACK_TENANT);
}

if (! defined("HELIOS_OPENSTACK_REGION")) {
    define("HELIOS_OPENSTACK_REGION", OPENSTACK_REGION);
}

if (! defined("HELIOS_OPENSTACK_SWIFT_CONTAINER_PREFIX")) {
    define("HELIOS_OPENSTACK_SWIFT_CONTAINER_PREFIX", OPENSTACK_SWIFT_CONTAINER_PREFIX);
}


/** OpenStack pour Helios (PES AQUIT) (si différent) */

if (! defined("HELIOS_ACQUIT_OPENSTACK_AUTHENTICATION_URL_V3")) {
    define("HELIOS_ACQUIT_OPENSTACK_AUTHENTICATION_URL_V3", OPENSTACK_AUTHENTICATION_URL_V3);
}

if (! defined("HELIOS_ACQUIT_OPENSTACK_USERNAME")) {
    define("HELIOS_ACQUIT_OPENSTACK_USERNAME", OPENSTACK_USERNAME);
}

if (! defined("HELIOS_ACQUIT_OPENSTACK_PASSWORD")) {
    define("HELIOS_ACQUIT_OPENSTACK_PASSWORD", OPENSTACK_PASSWORD);
}

if (! defined("HELIOS_ACQUIT_OPENSTACK_TENANT")) {
    define("HELIOS_ACQUIT_OPENSTACK_TENANT", OPENSTACK_TENANT);
}

if (! defined("HELIOS_ACQUIT_OPENSTACK_REGION")) {
    define("HELIOS_ACQUIT_OPENSTACK_REGION", OPENSTACK_REGION);
}

if (! defined("HELIOS_ACQUIT_OPENSTACK_SWIFT_CONTAINER_PREFIX")) {
    define("HELIOS_ACQUIT_OPENSTACK_SWIFT_CONTAINER_PREFIX", OPENSTACK_SWIFT_CONTAINER_PREFIX);
}

/** OpenStack pour Helios (PES RETOUR) (si différent) */

if (! defined("HELIOS_RETOUR_OPENSTACK_AUTHENTICATION_URL_V3")) {
    define("HELIOS_RETOUR_OPENSTACK_AUTHENTICATION_URL_V3", OPENSTACK_AUTHENTICATION_URL_V3);
}

if (! defined("HELIOS_RETOUR_OPENSTACK_USERNAME")) {
    define("HELIOS_RETOUR_OPENSTACK_USERNAME", OPENSTACK_USERNAME);
}

if (! defined("HELIOS_RETOUR_OPENSTACK_PASSWORD")) {
    define("HELIOS_RETOUR_OPENSTACK_PASSWORD", OPENSTACK_PASSWORD);
}

if (! defined("HELIOS_RETOUR_OPENSTACK_TENANT")) {
    define("HELIOS_RETOUR_OPENSTACK_TENANT", OPENSTACK_TENANT);
}

if (! defined("HELIOS_RETOUR_OPENSTACK_REGION")) {
    define("HELIOS_RETOUR_OPENSTACK_REGION", OPENSTACK_REGION);
}

if (! defined("HELIOS_RETOUR_OPENSTACK_SWIFT_CONTAINER_PREFIX")) {
    define("HELIOS_RETOUR_OPENSTACK_SWIFT_CONTAINER_PREFIX", OPENSTACK_SWIFT_CONTAINER_PREFIX);
}

/** OpenStack pour le mail sécurisé (si différent) */

if (! defined("MAILSEC_OPENSTACK_AUTHENTICATION_URL_V3")) {
    define("MAILSEC_OPENSTACK_AUTHENTICATION_URL_V3", OPENSTACK_AUTHENTICATION_URL_V3);
}

if (! defined("MAILSEC_OPENSTACK_USERNAME")) {
    define("MAILSEC_OPENSTACK_USERNAME", OPENSTACK_USERNAME);
}

if (! defined("MAILSEC_OPENSTACK_PASSWORD")) {
    define("MAILSEC_OPENSTACK_PASSWORD", OPENSTACK_PASSWORD);
}

if (! defined("MAILSEC_OPENSTACK_TENANT")) {
    define("MAILSEC_OPENSTACK_TENANT", OPENSTACK_TENANT);
}

if (! defined("MAILSEC_OPENSTACK_REGION")) {
    define("MAILSEC_OPENSTACK_REGION", OPENSTACK_REGION);
}

if (! defined("MAILSEC_OPENSTACK_SWIFT_CONTAINER_PREFIX")) {
    define("MAILSEC_OPENSTACK_SWIFT_CONTAINER_PREFIX", OPENSTACK_SWIFT_CONTAINER_PREFIX);
}

## Configuration connecteur DOCKER

if (! defined("PADES_VALID_URL")) {
    define("PADES_VALID_URL", "http://pades-valid:8080");
}

if (! defined("PDF_STAMP_URL")) {
    define("PDF_STAMP_URL", "http://pdf-stamp:8080");
}

# Macro permettant de définir le nombre de jours de rétention des actes
# lors de l'utilisation du script purge-transactions.php
if (! defined("ACTES_RETENTION_FICHIERS_NB_JOURS")) {
    define("ACTES_RETENTION_FICHIERS_NB_JOURS", 3650000);
}


# Macro permettant de définir le nombre de jours de rétention des PES_RETOUR
# lors de l'utilisation du script purge-transactions.php
if (! defined("HELIOS_RETENTION_RETOURS_NB_JOURS")) {
    define("HELIOS_RETENTION_RETOURS_NB_JOURS", 3650000);
}




//Ceci doit toujours etre la fin du fichier
require_once('config-static.php');
