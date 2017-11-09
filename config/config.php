<?php
require_once( __DIR__ . "/LoadLocalSettings.php");


ini_set("error_reporting", E_ALL & ~E_STRICT);

date_default_timezone_set("Europe/Paris");

//Attention, changement de la locale LC_TIME : ne pas la redéfinir dans le fichier LocalSettings !!!
//Cette locale est cohérente avec le code de S2low
setlocale(LC_TIME, "fr_FR.UTF-8");

if ( ! defined("OPERATEUR_DE_TELETRANSMISSION")) {
    define("OPERATEUR_DE_TELETRANSMISSION", "Libriciel SCOP");
}


if(!defined("EMAIL_ADMIN")){
        define("EMAIL_ADMIN","eric@sigmalis.com");
}

if(!defined("WEBSITE_TITLE")){
        define('WEBSITE_TITLE', "Tiers de télétransmission multiprotocoles");
}

if(!defined('WEBSITE')){
        define('WEBSITE', "http://192.168.1.28:4443/");
}

if(!defined('WEBSITE_SSL')){
        define('WEBSITE_SSL', "https://192.168.1.28:4443/");
}

if(!defined('WEBMASTER')){
        define('WEBMASTER', "webmaster@invalid.fr");
}

// Adresse du From des emails émis
if(!defined('TDT_FROM_EMAIL')){
        define('TDT_FROM_EMAIL', "Tiers de télétransmission <tedetis@invalid.fr>");
}


// Paramètres base de données
if(!defined('DB_HOST')){
        define('DB_HOST', "localhost");
}

if(!defined('DB_USER')){
        define('DB_USER', "tedetis");
}

if(!defined('DB_PASSWORD')){
        define('DB_PASSWORD', "tedetis");
}

if(!defined('DB_DATABASE')){
	define('DB_DATABASE', "tedetis");
}

if(!defined('DB_CLIENT_ENCODING')){
	define('DB_CLIENT_ENCODING', "LATIN9");
}



//Définition de la connexion à la base de données pour les tests unitaires et les tests de validation
if(!defined('DB_HOST_TEST')){
	define('DB_HOST_TEST', "localhost");
}

if(!defined('DB_USER_TEST')){
	define('DB_USER_TEST', "tedetis");
}

if(!defined('DB_PASSWORD_TEST')){
	define('DB_PASSWORD_TEST', "tedetis");
}

if(!defined('DB_DATABASE_TEST')){
	define('DB_DATABASE_TEST', "tedetis_test");
}

if (! defined('PHP_UNIT_AUTOLOADER')) {
	define("PHP_UNIT_AUTOLOADER", "../pastell/ext/composer/vendor/autoload.php");
}

// Nombre d'élément affichés par défaut par page dans les listes
if(!defined('DEFAULT_ITEMS_PER_PAGE')){
        define('DEFAULT_ITEMS_PER_PAGE', 10);
}

// Mode de l'application : dev ou prod (utilisé dans Database.class.php)
if(!defined("MODE")){
        //define("MODE", "prod");
        define("MODE", "dev");
}


// Permission des fichiers et répertoires générés
if (MODE == "dev"){
	define('GENERATED_DIRS_PERMS', 0777);
	define('GENERATED_FILES_PERMS', 0666);
} else {
	define('GENERATED_DIRS_PERMS', 0770);
	define('GENERATED_FILES_PERMS', 0660);
}

// Emplacement certificat/clef privée pour l'horodatage des logs
if(!defined('TIMESTAMPING_CERT')){
        define('TIMESTAMPING_CERT', '/etc/tedetis/ssl/tedetis_timestamp_cert.pem');
}

if(!defined('TIMESTAMPING_PRIV_KEY')){
        define('TIMESTAMPING_PRIV_KEY', '/etc/tedetis/ssl/tedetis_timestamp_priv_key.pem');
}

if(!defined('TIMESTAMPING_PRIV_KEY_PASS')){
        define('TIMESTAMPING_PRIV_KEY_PASS', '/etc/tedetis/ssl/tedetis_timestamp_priv_key.pass');
}

//Constante pour l'horodatage
//Chemin vers openssl version > 1.0.0a
if(!defined("OPENSSL_PATH")){
        define("OPENSSL_PATH","/Users/eric/Logiciel/openssl-1.0.0l/apps/openssl");
}

//WDSL du service d'horodatage
if(!defined("OPENSIGN_WSDL")){
        define("OPENSIGN_WSDL","http://horodatage.services.adullact.org/opensign.wsdl");
}

//Autorité de certification qui a signé le certificat de l'horodateur (obligatoire à cause d'une limitation d'openssl)
if(!defined("OPENSIGN_CA")){
        define("OPENSIGN_CA",__DIR__."/../data-exemple/root_ca.crt");
}

//Certificat de l'horodateur
if(!defined("OPENSIGN_CRT")){
        define("OPENSIGN_CRT",__DIR__."/../data-exemple/ts.crt");
}

//Temps en seconde avant de considérer l'horodateur en timeout
if(!defined("OPENSIGN_TIMEOUT")){
        define("OPENSIGN_TIMEOUT",2);
}

// Constantes générales
if(!defined('TRACE_FILE_PATH')){
	define('TRACE_FILE_PATH','/tmp/slow.log');
}

if(!defined('ANTIVIRUS_COMMAND')){
        define('ANTIVIRUS_COMMAND','/usr/bin/clamdscan');
}

if (!defined("ANTIVIRUS_UPSTART_TOUCH_FILE")){
	define("ANTIVIRUS_UPSTART_TOUCH_FILE","/tmp/antivirus-upstart");
}


if(!defined('TEDETIS_CERT_PATH')){
        define('TEDETIS_CERT_PATH','/home/tedetis/dev/certificat/site/dev.s2low-asoft.fr');
}

if(!defined('TEDETIS_KEY_PATH')){
        define('TEDETIS_KEY_PATH','/home/tedetis/dev/certificat/site/dev.s2low-asoft.fr-key');
}

if(!defined('TEDETIS_KEY_PASS')){
        define('TEDETIS_KEY_PASS','tedetis');
}

if(!defined('TEDETIS_TMP_PATH')){
        define('TEDETIS_TMP_PATH','/tmp/');
}

if(!defined("VERIFICATION_SIREN")){
    /** @deprecated VERIFICATION_SIREN*/
	define("VERIFICATION_SIREN",true);
}

//Paramètre pour l'outil de signature Libersign

if(!defined("LIBERSIGN_URL")){
        define("LIBERSIGN_URL","https://signature.services.adullact.org/libersign");
}

if(!defined("LIBERSIGN_HELP_URL")){
	define("LIBERSIGN_HELP_URL","https://www.libriciel.fr");
}

if (!defined("LIBERSIGN_EXTENSION_UPDATE_URL")){
	define("LIBERSIGN_EXTENSION_UPDATE_URL",false);
}

//Paramètre outils pour donner la forme canonique d'un document XML (C14N)
if (!defined("XML_STARLET_PATH")){
	define("XML_STARLET_PATH","/usr/bin/xmlstarlet");
}

//Paramètre outils de signature XML
if (!defined("XMLSEC1_PATH")){
	define("XMLSEC1_PATH","/usr/bin/xmlsec1");
}

//Emplacement d'un répertoire contenant le "hasher" des autorités de certification RGS
//voir "man c_rehash" pour le "hasher"
//Ce répertoire ne doit contenir que des certificats RGS et sert à signer et télétransmettre des flux Actes
if (! defined("RGS_VALIDCA_PATH")){
	define("RGS_VALIDCA_PATH","/etc/tedetis/ssl/validca/");
}

//Emplacement des certificats permettant la connexion à la plateforme ainsi que la signature et la télétransmission
//des flux PES
if (! defined("EXTENDED_VALIDCA_PATH")){
	define("EXTENDED_VALIDCA_PATH","/etc/tedetis/ssl/validca/");
}


//Permet de définir le nombre de mois pendant lequelle un enregistrement est gardé dans la table logs avant
//d'être déposé dans la table logs_historique
if (! defined("KEEP_NB_MONTHS_IN_LOGS")){
	define("KEEP_NB_MONTHS_IN_LOGS",6);
}

if (!defined("EXPORT_LOGS_DIRECTORY")){
	define("EXPORT_LOGS_DIRECTORY","/tdt-workspace/logs-export/");
}


//////////////////////////////////
///// Paramètre module Actes /////
//////////////////////////////////

if (!defined("ACTES_MINISTERE_ACRONYME")){
    //MISILL, MIAT, MIOCT, puis MIOCTI entre 2002 et 2012
    //MI - Ministère de l'intérieur - depuis le 16/05/2012
    define('ACTES_MINISTERE_ACRONYME','MI');
}

if (!defined('ACTES_MINISTERE_URL')){
    define('ACTES_MINISTERE_URL','http://simulateur/Simulateur/actesPost');
}

if (!defined('ACTES_MINISTERE_AUTHENTICATION')){
    #One off NONE, POST or BASIC
    define('ACTES_MINISTERE_AUTHENTICATION','NONE');
}

if (!defined('ACTES_MINISTERE_LOGIN')){
    define('ACTES_MINISTERE_LOGIN','');
}

if (!defined('ACTES_MINISTERE_PASSWORD')){
    define('ACTES_MINISTERE_PASSWORD','');
}

/* Il s'agit du certificat du client ! */
if (!defined('ACTES_MINISTERE_CERTIFICATE')){
    define('ACTES_MINISTERE_CERTIFICATE','');
}

if (!defined('ACTES_MINISTERE_CERTIFICATE_KEY')){
    define('ACTES_MINISTERE_CERTIFICATE_KEY','');
}

if (!defined('ACTES_MINISTERE_CERTIFICATE_KEY_PASS')){
    define('ACTES_MINISTERE_CERTIFICATE_KEY_PASS','');
}

if (!defined('ACTES_MINISTERE_SERVER_CERTIFICATE_PATH')){
    define('ACTES_MINISTERE_SERVER_CERTIFICATE_PATH','');
}

if (!defined('ACTES_IMAP_HOST')){
    define('ACTES_IMAP_HOST','mail');
}

if (!defined('ACTES_IMAP_PORT')){
    define('ACTES_IMAP_PORT','143');
}

if (!defined('ACTES_IMAP_LOGIN')){
    define('ACTES_IMAP_LOGIN','mail@tedetis.org');
}

if (!defined('ACTES_IMAP_PASSWORD')){
    define('ACTES_IMAP_PASSWORD','password');
}

// Nom de l'application vis à vis du MIAT
if(!defined('ACTES_APPLI_NAME')){
        define('ACTES_APPLI_NAME', 'TACT');
}

// Trigramme de l'application pour la génération des noms d'archive .tar.gz
if(!defined('ACTES_APPLI_TRIGRAMME')){
        define('ACTES_APPLI_TRIGRAMME', 'abc');
}

if(!defined('ACTES_APPLI_QUADRIGRAMME')){
	define('ACTES_APPLI_QUADRIGRAMME', 'TACT');
}


// Taille maximum autorisée des archives (en octets)
if(!defined('ACTES_ARCHIVE_MAX_SIZE')){
        define('ACTES_ARCHIVE_MAX_SIZE', 150 * 1024 * 1024);
}

// Taille maximum pour les envois de fichiers par lot
if(!defined('ACTES_MAX_BATCH_UPLOAD_SIZE')){
        define('ACTES_MAX_BATCH_UPLOAD_SIZE', 150*1024*1024);
}


if(!defined('ANTIVIRUS_TMP_PATH')){
        define('ANTIVIRUS_TMP_PATH','/tmp/');
}

// Adresse életronique du TdT pour le retour des messages du MIAT
if(!defined('ACTES_TDT_MAIL_ADDRESS')){
        define('ACTES_TDT_MAIL_ADDRESS', 'tedetis@sigmalis.com');
}

// Répertoire de stockage des fichiers envoyés par les utilisateurs (archives .tar.gz)
if(!defined('ACTES_FILES_UPLOAD_ROOT')){
        define('ACTES_FILES_UPLOAD_ROOT', '/tdt-workspace/actes/uploads');
}

// Répertoire de stockage des fichiers constituant les lots
if(!defined('ACTES_BATCHES_UPLOAD_ROOT')){
        define('ACTES_BATCHES_UPLOAD_ROOT', '/tdt-workspace/actes/batchs');
}

// Répertoire temporaire de stockage des réponses du ministère par mail
if(!defined('ACTES_RESPONSE_TMP_LOCAL_PATH')){
    define('ACTES_RESPONSE_TMP_LOCAL_PATH', '/data/tdt-workspace/actes/response_tmp');
}

// Répertoire temporaire de stockage des réponses en erreur du ministère
if(!defined('ACTES_RESPONSE_ERROR_PATH')){
    define('ACTES_RESPONSE_ERROR_PATH', '/data/tdt-workspace/actes/response_error');
}


// Liste des adresses de destinataires des notification commune écoutes les collectivités de l'instance
if(!defined('ACTES_COMMON_BROADCAST_EMAILS')){
	define('ACTES_COMMON_BROADCAST_EMAILS', 'eric@sigmalis.com');
} 

// Restreindre ou non plusieurs demandes de classification par jour par collectivité (1 par jour si restreint)
if(!defined('ACTES_RESTRICT_CLASSIF_REQUEST_FREQUENCY')){
	define('ACTES_RESTRICT_CLASSIF_REQUEST_FREQUENCY', false);
}

// Message indiquant que le plugin d upload du traitement par lot est en mode dégradé
if(!defined('ACTES_BATCH_UPLOAD_PLUGIN_FALLBACK_MESSAGE')){
	define('ACTES_BATCH_UPLOAD_PLUGIN_FALLBACK_MESSAGE', "La version de votre navigateur ne permet pas d'utiliser les fonctionnalités de sélection multiple de fichiers de manière optimum.<br />Vous devez choisir vos fichiers un par un.");
}


//Permet sur un site de formation ou de démonstration de ne pas attendre la durée légale de deux mois avant de valider une transaction ACTE.
if (!defined('ACTES_ALWAYS_CAN_VALIDATE')){
	define('ACTES_ALWAYS_CAN_VALIDATE',false);
}


//////////////////////////////////
///// Paramètre module Helios /////
/////////////////////////////////
if(!defined('HELIOS_FILES_ROOT')){
	define('HELIOS_FILES_ROOT', '/tdt-workspace/helios/');
}

// Répertoire de stockage des fichiers envoyés par les utilisateurs
if(!defined('HELIOS_FILES_UPLOAD_ROOT')){
	define('HELIOS_FILES_UPLOAD_ROOT', '/tdt-workspace/helios/sending/');
}
// Repertoire de stockage des reponses
if(!defined('HELIOS_RESPONSES_ROOT')){
	define('HELIOS_RESPONSES_ROOT', '/tdt-workspace/helios/response/');
}

// Repertoire de stockage des reponses en erreur 
if(!defined('HELIOS_RESPONSES_ERROR_PATH')){
	define('HELIOS_RESPONSES_ERROR_PATH', '/tdt-workspace/helios/response_error/');
}


// Repertoire de stockage des fichiers temporaires à envoyer au FTP
if(!defined('HELIOS_FILES_UPLOAD_TMP')){
	define('HELIOS_FILES_UPLOAD_TMP', '/tdt-workspace/helios/sending-tmp/');
}



//Pour le script d'analyse des enveloppe Helios à envoyer
if(!defined('HELIOS_VALIDATION_UPSTART_TOUCH_FILE')){
	define('HELIOS_VALIDATION_UPSTART_TOUCH_FILE','/tmp/helios-validation-upstart');
}


if(!defined('HELIOS_COUNTER_FILE')){
	define('HELIOS_COUNTER_FILE',"/tdt-workspace/helios/counter.txt");
}

if(!defined("HELIOS_ZIP_BEFORE_SEND")){
	define("HELIOS_ZIP_BEFORE_SEND",false);
}
//Mettre "" pour la production, mettre un répertoire de destination pour un serveur de test
//Faire terminer la destination par un /
if(!defined("HELIOS_SENDING_DESTINATION")){
	define("HELIOS_SENDING_DESTINATION","");
}

if(!defined("HELIOS_SENDING_MODE_DEMO")){
	define("HELIOS_SENDING_MODE_DEMO",true);
}

//Pour le script de récupération des enveloppe Helios
if(!defined('HELIOS_FTP_SERVER')){
	define('HELIOS_FTP_SERVER','127.0.0.1');
}

if (!defined('HELIOS_FTP_PASSIVE_MODE')){
	define('HELIOS_FTP_PASSIVE_MODE',true);
}

if(!defined('HELIOS_FTP_PORT')){
	define('HELIOS_FTP_PORT','21');
}

if(!defined('HELIOS_FTP_LOGIN')){
	define('HELIOS_FTP_LOGIN','helios');
}

if(!defined('HELIOS_FTP_PASSWORD')){
	define('HELIOS_FTP_PASSWORD','helios');
}

if(!defined('HELIOS_FTP_RESPONSE_SERVER_PATH')){
	define('HELIOS_FTP_RESPONSE_SERVER_PATH','/home/helios/response/');
}

if(!defined('HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH')){
	define('HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH','/tdt-workspace/helios/response_tmp/');
}

if(!defined('HELIOS_UPSTART_TOUCH_FILE')){
	define('HELIOS_UPSTART_TOUCH_FILE','/tmp/helios-upstart');
}

if(!defined('HELIOS_MAX_UPLOAD_SIZE')){
	define('HELIOS_MAX_UPLOAD_SIZE',128*1024*1024);
}

if(!defined('HELIOS_GENERATED_FILE_PERMISSION')){
	define('HELIOS_GENERATED_FILE_PERMISSION','0644');
}

if (!defined('HELIOS_SIGNATURE_PLATEFORME_CLAIMED_ROLE')){
	define('HELIOS_SIGNATURE_PLATEFORME_CLAIMED_ROLE','Rôle invalide à configurer');
}

if (! defined('HELIOS_SIGNATURE_PLATEFORME_COUNTRY_NAME')){
	define('HELIOS_SIGNATURE_PLATEFORME_COUNTRY_NAME','France');
}

if (! defined('HELIOS_SIGNATURE_PLATEFORME_POSTAL_CODE')){
	define('HELIOS_SIGNATURE_PLATEFORME_POSTAL_CODE','34000');
}

if (! defined('HELIOS_SIGNATURE_PLATEFORME_CITY')){
	define('HELIOS_SIGNATURE_PLATEFORME_CITY','Montpellier');
}

if (!defined('HELIOS_PLATEFORME_CERTIFICATE_P12')){
	define('HELIOS_PLATEFORME_CERTIFICATE_P12',__DIR__."/../data-exemple/plateforme-cert.p12");
}

if (! defined('HELIOS_PLATEFORME_CERTIFICATE_PASSWORD')){
	define('HELIOS_PLATEFORME_CERTIFICATE_PASSWORD','robert_petitpoids');
}

if (!defined("HELIOS_ENABLE_SIGNATURE_TECHNIQUE")){
	define("HELIOS_ENABLE_SIGNATURE_TECHNIQUE",false);
}

if (empty($helios_authorized_rollback_ids)){
	$helios_authorized_rollback_ids = array();
}

if (!defined("HELIOS_OCRE_FILE_PATH")){
	define("HELIOS_OCRE_FILE_PATH","/tdt-workspace/helios/ocre/");
}

if (!defined("HELIOS_OCRE_EXPORT_URL")){
	define("HELIOS_OCRE_EXPORT_URL","http://localhost/phpstorm/pastell-ocre/script/receive-ocre.php");
}

if (!defined("HELIOS_OCRE_PASSWORD")){
	define("HELIOS_OCRE_PASSWORD","changme");
}

if ( ! defined("HELIOS_DO_NOT_VERIFY_NOM_FIC_UNICITY")){
	//Permet de faire sauter la vérication de l'unicité du NomFic dans les PES_Aller
	//Il faut mettre cette constante à faux et explicitement coché une case sur l'autorité sur la console
	define("HELIOS_DO_NOT_VERIFY_NOM_FIC_UNICITY", false);
}


//////////////////////////////////
///// Paramètre module Etat Civil /////
/////////////////////////////////
// Répertoire de stockage des fichiers envoyés par les utilisateurs
if(!defined('ETAT_CIVIL_FILES_UPLOAD_ROOT')){
	define('ETAT_CIVIL_FILES_UPLOAD_ROOT', '/home/tedetis/dev/tdt-workspace/uploads/etat_civil');
}

//////////////////////////////
///// Paramètre module Mail  /////
//////////////////////////////////
// Répertoire de stockage des fichiers envoyés par les utilisateurs
if(!defined('MAIL_FILES_UPLOAD_ROOT')){
	define('MAIL_FILES_UPLOAD_ROOT', '/tdt-workspace/mail/');
}

if(!defined('MAIL_TEDETIS_FROM')){
	define('MAIL_TEDETIS_FROM','tedetis-mailsec@adullact.fr');
}

if(!defined('IMAP_LOGIN')){
	define('IMAP_LOGIN','tedetis-mailsec');
}

if(!defined('IMAP_PASS')){
	define('IMAP_PASS','Yi7eequa');
}

if(!defined('IMAP_SERVER')){
	define('IMAP_SERVER','mail.ntsys.fr');
}

if(!defined('MAIL_MESSAGE')){
	define('MAIL_MESSAGE',"Vous avez reçu un courrier recommandé par S2LOW");
}

if(!defined('MAIL_TEXT')){
	define('MAIL_TEXT',"Bonjour,
    
Un courrier recommandé avec d'éventuelles pièces jointes vous a été transmis par la plateforme sécurisée de l'Adullact. Nous vous proposons de cliquer sur le lien suivant pour accéder au courrier recommandé et confirmer la réception : accès au courrier recommandé.");
}
/*************************
 * Paramètre module DIA
 */

//Répertoire pour les DIA utilisé dans S²low
if(!defined("DIA_UPLOAD_PATH")){
	define("DIA_UPLOAD_PATH","/tdt-workspace/dia/upload");
}

//Répertoire pour les DIA reçu via PEC/PRESTO
if(!defined("DIA_DELIVERY_PATH")){
	define("DIA_DELIVERY_PATH","/tdt-workspace/dia/delivery");
}

//Répertoire pour les envois vers PEC/PRESTO
if(!defined("DIA_TO_PRESTO")){
	define("DIA_TO_PRESTO","/tdt-workspace/dia/to-presto");
}

if(!defined("DIA_UPSTART_TOUCH_FILE")){
	define("DIA_UPSTART_TOUCH_FILE",'/tmp/dia-upstart');
}

if (! defined("TESTING_ENVIRONNEMENT")) {
	define("TESTING_ENVIRONNEMENT", false);
}

if (! defined("LIBERSIGN_INSTALLER")){
	# Uniquement pour l'installation Docker afin que celui-ci puisse récupérer le script d'installation de Libersign
	define("LIBERSIGN_INSTALLER",false);
}


## Configuration OpenStack (facultative)

if (! defined("OPENSTACK_AUTHENTICATION_URL_V2")) {
    define("OPENSTACK_AUTHENTICATION_URL_V2", 'https://identity.fr1.cloudwatt.com/v2.0');
}

if (! defined("OPENSTACK_USERNAME")) {
    define("OPENSTACK_USERNAME","");
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

if (! defined("PADES_VALID_URL")){
    define("PADES_VALID_URL","http://pades-valid:8080");
}

if (! defined("PDF_STAMP_URL")){
    define("PDF_STAMP_URL","http://pdf-stamp:8080");
}

//Ceci doit toujours etre la fin du fichier
require_once('config-static.php');
//Bon ok c'est bizarre, mais c'est comme les script les plus vieux ne charge que config.php à la place de init.php
//on fait en sorte que config.php charge init.php et réciproquement
require_once(__DIR__."/../init/init.php");

