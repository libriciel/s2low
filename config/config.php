<?php

/* Quel bordel, des fois on appel config.php, des fois init.php. Je rajoute ca la a cause de Monolog */
require_once __DIR__."/../vendor/autoload.php";

require_once( __DIR__ . "/LoadLocalSettings.php");


ini_set("error_reporting", E_ALL & ~E_STRICT);

date_default_timezone_set("Europe/Paris");

//Attention, changement de la locale LC_TIME : ne pas la red�finir dans le fichier LocalSettings !!!
//Cette locale est coh�rente avec le code de S2low
setlocale(LC_TIME, "fr_FR.UTF-8");

if ( ! defined("OPERATEUR_DE_TELETRANSMISSION")) {
    define("OPERATEUR_DE_TELETRANSMISSION", "Libriciel SCOP");
}


if(!defined("EMAIL_ADMIN")){
        define("EMAIL_ADMIN","noreply@s2low.docker.libriciel.fr");
}

// Adresse email sur laquelle seront re�u les alertes critiques du syst�me n�cessitant une intervention imm�diate
// Possibilit� de mettre plusieurs emails s�par�s par des virgules
if (!defined("EMAIL_ADMIN_TECHNIQUE")){
	define("EMAIL_ADMIN_TECHNIQUE","noreply@noreplyaaaaa.aaaa");
}

if(!defined("WEBSITE_TITLE")){
        define('WEBSITE_TITLE', "Tiers de t�l�ransmission multiprotocoles");
}

if(!defined('WEBSITE')){
        define('WEBSITE', "http://s2low.docker.libriciel.fr/");
}

if(!defined('WEBSITE_SSL')){
        define('WEBSITE_SSL', "https://s2low.docker.libriciel.fr/");
}

if(!defined('WEBMASTER')){
        define('WEBMASTER', "webmaster@s2low.docker.libriciel.fr");
}

// Adresse du From des emails �mis
if(!defined('TDT_FROM_EMAIL')){
        define('TDT_FROM_EMAIL', "Tiers de t�l�transmission <tedetis@s2low.docker.libriciel.fr>");
}

if (!defined('LOG_FILE')){
	define('LOG_FILE','/data/log/s2low.log');
}

if (!defined('LOG_LEVEL')){
	define('LOG_LEVEL',Monolog\Logger::INFO);
}


// Param�tres base de donn�es
if(!defined('DB_HOST')){
        define('DB_HOST', "db");
}

if(!defined('DB_USER')){
        define('DB_USER', "s2lowuser");
}

if(!defined('DB_PASSWORD')){
        define('DB_PASSWORD', "s2lowpassword");
}

if(!defined('DB_DATABASE')){
	define('DB_DATABASE', "s2lowdb");
}

if(!defined('DB_CLIENT_ENCODING')){
	define('DB_CLIENT_ENCODING', "LATIN9");
}


/**
 * Active le mode beanstakld : les jobs sont envoy�s sur le serveur beanstakld
 *
 */
if(!defined("MODE_BEANSTALKD")){
	define("MODE_BEANSTALKD",false);
}

if (!defined("BEANSTAKLD_SERVER")){
	define("BEANSTAKLD_SERVER","beanstalkd");
}

if (!defined("BEANSTAKLD_PORT")){
	define("BEANSTAKLD_PORT","11300");
}


//D�finition de la connexion � la base de donn�es pour les tests unitaires et les tests de validation
if(!defined('DB_HOST_TEST')){
	define('DB_HOST_TEST', "dbtest");
}

if(!defined('DB_USER_TEST')){
	define('DB_USER_TEST', "s2lowusertest");
}

if(!defined('DB_PASSWORD_TEST')){
	define('DB_PASSWORD_TEST', "s2lowpasswordtest");
}

if(!defined('DB_DATABASE_TEST')){
	define('DB_DATABASE_TEST', "s2lowdbtest");
}

if (! defined('PHP_UNIT_AUTOLOADER')) {
	define("PHP_UNIT_AUTOLOADER", "../pastell/ext/composer/vendor/autoload.php");
}

// Nombre d'�l�ment affich�s par d�faut par page dans les listes
if(!defined('DEFAULT_ITEMS_PER_PAGE')){
        define('DEFAULT_ITEMS_PER_PAGE', 10);
}

// Mode de l'application : dev ou prod
if(!defined("MODE")){
        //define("MODE", "prod");
        define("MODE", "dev");
}


// Permission des fichiers et r�pertoires g�n�r�s
if (MODE == "dev"){
	define('GENERATED_DIRS_PERMS', 0777);
	define('GENERATED_FILES_PERMS', 0666);
} else {
	define('GENERATED_DIRS_PERMS', 0770);
	define('GENERATED_FILES_PERMS', 0660);
}

// Emplacement certificat/clef priv�e pour l'horodatage des logs
if(!defined('TIMESTAMPING_CERT')){
        define('TIMESTAMPING_CERT', '/etc/s2low/ssl/tedetis_timestamp_cert.pem');
}

if(!defined('TIMESTAMPING_PRIV_KEY')){
        define('TIMESTAMPING_PRIV_KEY', '/etc/s2low/ssl/tedetis_timestamp_priv_key.pem');
}

if(!defined('TIMESTAMPING_PRIV_KEY_PASS')){
        define('TIMESTAMPING_PRIV_KEY_PASS', '/etc/s2low/ssl/tedetis_timestamp_priv_key.pass');
}

//Constante pour l'horodatage
//Chemin vers openssl version > 1.0.0a
if(!defined("OPENSSL_PATH")){
        define("OPENSSL_PATH","/usr/bin/openssl");
}

//WDSL du service d'horodatage
if(!defined("OPENSIGN_WSDL")){
        define("OPENSIGN_WSDL","http://horodatage.services.adullact.org/opensign.wsdl");
}

//Autorit� de certification qui a sign� le certificat de l'horodateur (obligatoire � cause d'une limitation d'openssl)
if(!defined("OPENSIGN_CA")){
        define("OPENSIGN_CA",__DIR__."/../data-exemple/root_ca.crt");
}

//Certificat de l'horodateur
if(!defined("OPENSIGN_CRT")){
        define("OPENSIGN_CRT",__DIR__."/../data-exemple/ts.crt");
}

//Temps en seconde avant de consid�rer l'horodateur en timeout
if(!defined("OPENSIGN_TIMEOUT")){
        define("OPENSIGN_TIMEOUT",2);
}

// Constantes g�n�rales
if(!defined('TRACE_FILE_PATH')){
	define('TRACE_FILE_PATH','/data/log/slow.log');
}

if(!defined('ANTIVIRUS_COMMAND')){
        define('ANTIVIRUS_COMMAND','/usr/bin/clamdscan');
}

if(!defined('TEDETIS_TMP_PATH')){
        define('TEDETIS_TMP_PATH','/tmp/');
}

if(!defined("VERIFICATION_SIREN")){
    /** @deprecated VERIFICATION_SIREN*/
	define("VERIFICATION_SIREN",true);
}

//Param�tre pour l'outil de signature Libersign

if(!defined("LIBERSIGN_URL")){
        define("LIBERSIGN_URL",WEBSITE_SSL."/libersign/");
}

if(!defined("LIBERSIGN_HELP_URL")){
	define("LIBERSIGN_HELP_URL","https://www.libriciel.fr");
}

if (!defined("LIBERSIGN_EXTENSION_UPDATE_URL")){
    define("LIBERSIGN_EXTENSION_UPDATE_URL",WEBSITE_SSL."/libersign/");
}

if (! defined("LIBERSIGN_INSTALLER")){
    define("LIBERSIGN_INSTALLER","https://libersign.libriciel.fr/make.sh");
}

//Param�tre outils pour donner la forme canonique d'un document XML (C14N)
if (!defined("XML_STARLET_PATH")){
	define("XML_STARLET_PATH","/usr/bin/xmlstarlet");
}

//Param�tre outils de signature XML
if (!defined("XMLSEC1_PATH")){
	define("XMLSEC1_PATH","/usr/bin/xmlsec1");
}

//Emplacement d'un r�pertoire contenant le "hasher" des autorit�s de certification RGS
//voir "man c_rehash" pour le "hasher"
//Ce r�pertoire ne doit contenir que des certificats RGS et sert � signer et t�l�transmettre des flux Actes
if (! defined("RGS_VALIDCA_PATH")){
	define("RGS_VALIDCA_PATH","/etc/s2low/ssl/validca/");
}

//Emplacement des certificats permettant la connexion � la plateforme ainsi que la signature et la t�l�transmission
//des flux PES
if (! defined("EXTENDED_VALIDCA_PATH")){
	define("EXTENDED_VALIDCA_PATH","/etc/s2low/ssl/validca/");
}


//Permet de d�finir le nombre de mois pendant lequelle un enregistrement est gard� dans la table logs avant
//d'�tre d�pos� dans la table logs_historique
if (! defined("KEEP_NB_MONTHS_IN_LOGS")){
	define("KEEP_NB_MONTHS_IN_LOGS",6);
}

if (!defined("EXPORT_LOGS_DIRECTORY")){
	define("EXPORT_LOGS_DIRECTORY","/data/tdt-workspace/logs-export/");
}


//////////////////////////////////
///// Param�tre module Actes /////
//////////////////////////////////

if (!defined("ACTES_MINISTERE_ACRONYME")){
    //MISILL, MIAT, MIOCT, puis MIOCTI entre 2002 et 2012
    //MI - Minist�re de l'int�rieur - depuis le 16/05/2012
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
    define('ACTES_IMAP_LOGIN','s2low@s2low.docker.libriciel.fr');
}

if (!defined('ACTES_IMAP_PASSWORD')){
    define('ACTES_IMAP_PASSWORD','password');
}

// Nom de l'application vis � vis du MIAT
if(!defined('ACTES_APPLI_NAME')){
        define('ACTES_APPLI_NAME', 'TACT');
}

// Trigramme de l'application pour la g�n�ration des noms d'archive .tar.gz
if(!defined('ACTES_APPLI_TRIGRAMME')){
        define('ACTES_APPLI_TRIGRAMME', 'abc');
}

if(!defined('ACTES_APPLI_QUADRIGRAMME')){
	define('ACTES_APPLI_QUADRIGRAMME', 'TACT');
}


// Taille maximum autoris�e des archives (en octets)
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

// Adresse �letronique du TdT pour le retour des messages du MIAT
if(!defined('ACTES_TDT_MAIL_ADDRESS')){
        define('ACTES_TDT_MAIL_ADDRESS', 's2low@s2low.docker.libriciel.fr');
}

// R�pertoire de stockage des fichiers envoy�s par les utilisateurs (archives .tar.gz)
if(!defined('ACTES_FILES_UPLOAD_ROOT')){
        define('ACTES_FILES_UPLOAD_ROOT', '/data/tdt-workspace/actes/uploads');
}

// R�pertoire de stockage des fichiers constituant les lots
if(!defined('ACTES_BATCHES_UPLOAD_ROOT')){
        define('ACTES_BATCHES_UPLOAD_ROOT', '/data/tdt-workspace/actes/batchs');
}

// R�pertoire temporaire de stockage des r�ponses du minist�re par mail
if(!defined('ACTES_RESPONSE_TMP_LOCAL_PATH')){
    define('ACTES_RESPONSE_TMP_LOCAL_PATH', '/data/tdt-workspace/actes/response_tmp');
}

// R�pertoire temporaire de stockage des r�ponses en erreur du minist�re
if(!defined('ACTES_RESPONSE_ERROR_PATH')){
    define('ACTES_RESPONSE_ERROR_PATH', '/data/tdt-workspace/actes/response_error');
}


// Liste des adresses de destinataires des notification commune �coutes les collectivit�s de l'instance
if(!defined('ACTES_COMMON_BROADCAST_EMAILS')){
	define('ACTES_COMMON_BROADCAST_EMAILS', 'defaut@s2low.docker.libriciel.fr');
}

// Restreindre ou non plusieurs demandes de classification par jour par collectivit� (1 par jour si restreint)
if(!defined('ACTES_RESTRICT_CLASSIF_REQUEST_FREQUENCY')){
	define('ACTES_RESTRICT_CLASSIF_REQUEST_FREQUENCY', false);
}

// Message indiquant que le plugin d upload du traitement par lot est en mode d�grad�
if(!defined('ACTES_BATCH_UPLOAD_PLUGIN_FALLBACK_MESSAGE')){
	define('ACTES_BATCH_UPLOAD_PLUGIN_FALLBACK_MESSAGE', "La version de votre navigateur ne permet pas d'utiliser les fonctionnalit�s de s�lection multiple de fichiers de mani�re optimum.<br />Vous devez choisir vos fichiers un par un.");
}


//Permet sur un site de formation ou de d�monstration de ne pas attendre la dur�e l�gale de deux mois avant de valider une transaction ACTE.
if (!defined('ACTES_ALWAYS_CAN_VALIDATE')){
	define('ACTES_ALWAYS_CAN_VALIDATE',false);
}

//Permet de ne jamais valider les certificats de signatures des actes
if (!defined('ACTES_DONT_VALID_SIGNING_CERTIFICATE')){
	define('ACTES_DONT_VALID_SIGNING_CERTIFICATE',false);
}

//Le type de PJ est obligatoire, peut-�tre � partir du 08/06/2019
if (! defined("ACTES_TYPE_PJ_IS_MANDATORY")){

	//ACTES_TYPE_PJ_IS_MANDATORY == false => on v�rifie que le code existe, si le code n'est pas fourni, on envoi quand m�me
	//ACTES_TYPE_PJ_IS_MANDATORY == true => on v�rifie que le code existe et qu'il correspond � la nature donn�es, on bloque si pas de code

	define("ACTES_TYPE_PJ_IS_MANDATORY",false); // A compter du 08/06/2019, il faudrait le supprimer et modifier le code comme si cette valeur ne pouvait valoir que true
}

//L'ancienne notice permettait le choix en fonction de la nature et de la classification
//la nouvelle notice � compter du 08/06/2019 permet le choix en fonction de la nature uniquement et supprime le code 99_AU pour les actes hors de la nature autre.
if (! defined("ACTES_TYPE_PAR_NATURE")){

	//ACTES_TYPE_PAR_NATURE == false => on filtre les types par natures et classfication et on ajoute 99_AU syst�matiquement
	//ACTES_TYPE_PAR_NATURE == true => on filtre uniquement par nature

	define("ACTES_TYPE_PAR_NATURE",false); // A compter du 08/06/2019, il faudrait le supprimer et modifier le code comme si cette valeur ne pouvait valoir que true
}



//////////////////////////////////
///// Param�tre module Helios /////
/////////////////////////////////
if(!defined('HELIOS_FILES_ROOT')){
	define('HELIOS_FILES_ROOT', '/data/tdt-workspace/helios/');
}

// R�pertoire de stockage des fichiers envoy�s par les utilisateurs
if(!defined('HELIOS_FILES_UPLOAD_ROOT')){
	define('HELIOS_FILES_UPLOAD_ROOT', '/data/tdt-workspace/helios/sending/');
}
// Repertoire de stockage des reponses
if(!defined('HELIOS_RESPONSES_ROOT')){
	define('HELIOS_RESPONSES_ROOT', '/data/tdt-workspace/helios/response/');
}

// Repertoire de stockage des reponses en erreur
if(!defined('HELIOS_RESPONSES_ERROR_PATH')){
	define('HELIOS_RESPONSES_ERROR_PATH', '/data/tdt-workspace/helios/response_error/');
}


// Repertoire de stockage des fichiers temporaires � envoyer au FTP
if(!defined('HELIOS_FILES_UPLOAD_TMP')){
	define('HELIOS_FILES_UPLOAD_TMP', '/data/tdt-workspace/helios/sending-tmp/');
}



//Pour le script d'analyse des enveloppe Helios � envoyer
if(!defined('HELIOS_VALIDATION_UPSTART_TOUCH_FILE')){
	define('HELIOS_VALIDATION_UPSTART_TOUCH_FILE','/tmp/helios-validation-upstart');
}


if(!defined('HELIOS_COUNTER_FILE')){
	define('HELIOS_COUNTER_FILE',"/data/tdt-workspace/helios/counter.txt");
}

if(!defined("HELIOS_ZIP_BEFORE_SEND")){
	define("HELIOS_ZIP_BEFORE_SEND",false);
}
//Mettre "" pour la production, mettre un r�pertoire de destination pour un serveur de test
//Faire terminer la destination par un /
if(!defined("HELIOS_SENDING_DESTINATION")){
	define("HELIOS_SENDING_DESTINATION","/entree/");
}

if(!defined("HELIOS_SENDING_MODE_DEMO")){
	define("HELIOS_SENDING_MODE_DEMO",true);
}

//Pour le script de r�cup�ration des enveloppe Helios
if(!defined('HELIOS_FTP_SERVER')){
	define('HELIOS_FTP_SERVER','ftp');
}

if (!defined('HELIOS_FTP_PASSIVE_MODE')){
	define('HELIOS_FTP_PASSIVE_MODE',false);
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
	define('HELIOS_FTP_RESPONSE_SERVER_PATH','/sortie/');
}

if(!defined('HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH')){
	define('HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH','/data/tdt-workspace/helios/response_tmp/');
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
	define('HELIOS_SIGNATURE_PLATEFORME_CLAIMED_ROLE','R�le invalide � configurer');
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

if (!defined("HELIOS_OCRE_FILE_PATH")){
	define("HELIOS_OCRE_FILE_PATH","/data/tdt-workspace/helios/ocre/");
}

if (!defined("HELIOS_OCRE_EXPORT_URL")){
	define("HELIOS_OCRE_EXPORT_URL","http://localhost/phpstorm/pastell-ocre/script/receive-ocre.php");
}

if (!defined("HELIOS_OCRE_PASSWORD")){
	define("HELIOS_OCRE_PASSWORD","changme");
}

if ( ! defined("HELIOS_DO_NOT_VERIFY_NOM_FIC_UNICITY")){
	//Permet de faire sauter la v�rication de l'unicit� du NomFic dans les PES_Aller
	//Il faut mettre cette constante � faux et explicitement coch� une case sur l'autorit� sur la console
	define("HELIOS_DO_NOT_VERIFY_NOM_FIC_UNICITY", false);
}


//////////////////////////////////
///// Param�tre module Etat Civil /////
/////////////////////////////////
// R�pertoire de stockage des fichiers envoy�s par les utilisateurs
if(!defined('ETAT_CIVIL_FILES_UPLOAD_ROOT')){
	define('ETAT_CIVIL_FILES_UPLOAD_ROOT', '/data/tdt-workspace/uploads/etat_civil');
}

//////////////////////////////
///// Param�tre module Mail  /////
//////////////////////////////////
// R�pertoire de stockage des fichiers envoy�s par les utilisateurs
if(!defined('MAIL_FILES_UPLOAD_ROOT')){
	define('MAIL_FILES_UPLOAD_ROOT', '/data/tdt-workspace/mail/');
}

if(!defined('MAIL_TEDETIS_FROM')){
	define('MAIL_TEDETIS_FROM','s2low-mailsec@s2low.docker.libriciel.fr');
}

if(!defined('IMAP_LOGIN')){
	define('IMAP_LOGIN','s2low-mailsec@s2low.docker.libriciel.fr');
}

if(!defined('IMAP_PASS')){
	define('IMAP_PASS','s2low-mailsec');
}

if(!defined('IMAP_SERVER')){
	define('IMAP_SERVER','mailsec');
}

if(!defined('MAIL_MESSAGE')){
	define('MAIL_MESSAGE',"Vous avez re�u un courrier recommand� par S2LOW");
}

if(!defined('MAIL_TEXT')){
	define('MAIL_TEXT',"Bonjour,

Un courrier recommand� avec d'�ventuelles pi�ces jointes vous a �t� transmis par la plateforme s�curis�e de l'Adullact. Nous vous proposons de cliquer sur le lien suivant pour acc�der au courrier recommand� et confirmer la r�ception : acc�s au courrier recommand�.");
}
/*************************
 * Param�tre module DIA
 */

//R�pertoire pour les DIA utilis� dans S�low
if(!defined("DIA_UPLOAD_PATH")){
	define("DIA_UPLOAD_PATH","/data/tdt-workspace/dia/upload");
}

//R�pertoire pour les DIA re�u via PEC/PRESTO
if(!defined("DIA_DELIVERY_PATH")){
	define("DIA_DELIVERY_PATH","/data/tdt-workspace/dia/delivery");
}

//R�pertoire pour les envois vers PEC/PRESTO
if(!defined("DIA_TO_PRESTO")){
	define("DIA_TO_PRESTO","/data/tdt-workspace/dia/to-presto");
}

if(!defined("DIA_UPSTART_TOUCH_FILE")){
	define("DIA_UPSTART_TOUCH_FILE",'/tmp/dia-upstart');
}

if (! defined("TESTING_ENVIRONNEMENT")) {
	define("TESTING_ENVIRONNEMENT", false);
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

# Macro permettant de définir le nombre de jours de rétention des actes
# lors de l'utilisation du script purge-transactions.php
if (! defined("ACTES_RETENTION_FICHIERS_NB_JOURS")){
    define("ACTES_RETENTION_FICHIERS_NB_JOURS",3650000);
}

# Macro permettant de définir le nombre de jours de rétention des PES_ALLER
# lors de l'utilisation du script purge-transactions.php
if (! defined("HELIOS_RETENTION_FICHIERS_NB_JOURS")){
    define("HELIOS_RETENTION_FICHIERS_NB_JOURS",3650000);
}

# Macro permettant de définir le nombre de jours de rétention des PES_RETOUR
# lors de l'utilisation du script purge-transactions.php
if (! defined("HELIOS_RETENTION_RETOURS_NB_JOURS")){
    define("HELIOS_RETENTION_RETOURS_NB_JOURS",3650000);
}

//Ceci doit toujours etre la fin du fichier
require_once('config-static.php');
//Bon ok c'est bizarre, mais c'est comme les script les plus vieux ne charge que config.php � la place de init.php
//on fait en sorte que config.php charge init.php et r�ciproquement
require_once(__DIR__."/../init/init.php");
