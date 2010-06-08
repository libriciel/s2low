<?php

ini_set("error_reporting", E_ALL & ~E_NOTICE);

define('SITEROOT', "/home/eric/slow/TedetisPHP/");

define('WEBSITE_TITLE', "Tiers de télétransmission multiprotocoles");
define('WEBSITE', "http://127.0.0.3");
define('WEBSITE_SSL', "https://192.168.1.87");

define('WEBMASTER', "webmaster@invalid.fr");
// Adresse du From des emails émis
define('TDT_FROM_EMAIL', "Tiers de télétransmission <tedetis@invalid.fr>");


// Paramètres base de données
define('DB_HOST', "localhost");
define('DB_USER', "tedetis");
define('DB_PASSWORD', "tedetis");
define('DB_DATABASE', "tedetis");

// Nombre d'élément affichés par défaut par page dans les listes
define('DEFAULT_ITEMS_PER_PAGE', 10);

define('SUPPORT_URL', "http://support.invalid.fr/");
define('HOTLINE_NUM', "xx.xx.xx.xx.xx");


// Mode de l'application : dev ou prod (utilisï¿½ dans Database.class.php)
//define("MODE", "prod");
define("MODE", "dev");

// Permission des fichiers et rï¿½pertoires gï¿½nï¿½rï¿½s
if (MODE == "dev"){
	define('GENERATED_DIRS_PERMS', 0777);
	define('GENERATED_FILES_PERMS', 0666);
} else {
	define('GENERATED_DIRS_PERMS', 0770);
	define('GENERATED_FILES_PERMS', 0660);
}

// Emplacement certificat/clef privée pour l'horodatage des logs
define('TIMESTAMPING_CERT', '/etc/tedetis/ssl/tedetis_timestamp_cert.pem');
define('TIMESTAMPING_PRIV_KEY', '/etc/tedetis/ssl/tedetis_timestamp_priv_key.pem');
define('TIMESTAMPING_PRIV_KEY_PASS', '/etc/tedetis/ssl/tedetis_timestamp_priv_key.pass');


// Chemin vers les certificats des autorités autorisées pour la signature de documents
define('AUTHORIZED_SIGN_CA_PATH', '/etc/tedetis/ssl/validca');


// Constantes générales
define('TRACE_FILE_PATH','/tmp/tedetis-trace.log');

define('ANTIVIRUS_COMMAND','/usr/bin/clamdscan');

define('TEDETIS_CERT_PATH','/home/tedetis/dev/certificat/site/dev.s2low-asoft.fr');
define('TEDETIS_KEY_PATH','/home/tedetis/dev/certificat/site/dev.s2low-asoft.fr-key');
define('TEDETIS_KEY_PASS','tedetis');

define('TEDETIS_TMP_PATH','/tmp/');

define("VERIFICATION_SIREN",false);

//////////////////////////////////
///// Paramètre module Actes /////
//////////////////////////////////

// Nom de l'application vis à vis du MIAT
define('ACTES_APPLI_NAME', 'TACT');

// Trigramme de l'application pour la génération des noms d'archive .tar.gz
define('ACTES_APPLI_TRIGRAMME', 'abc');

// Taille maximum autorisée des archives (en octets)
define('ACTES_ARCHIVE_MAX_SIZE', 20 * 1024 * 1024);

// Taille maximum pour les envois de fichiers par lot
define('ACTES_MAX_BATCH_UPLOAD_SIZE', 20*1024*1024);

// Commande d'invocation du scanner anti-virus
define('ACTES_ANTIVIRUS_COMMAND', '/usr/bin/clamdscan --stdout ');
define('ANTIVIRUS_TMP_PATH',' /tmp/');


// Adresse de la servlet effectuant le controle des archives
define('ACTES_CHECK_ARCHIVE_SERVLET', 'http://127.0.0.1:8180/TedetisActes/ValideArchive');

// Adresse életronique du TdT pour le retour des messages du MIAT
define('ACTES_TDT_MAIL_ADDRESS', 'eric@babette.com');

// Répertoire de stockage des fichiers envoyés par les utilisateurs (archives .tar.gz)
define('ACTES_FILES_UPLOAD_ROOT', '/tdt-workspace/actes/uploads');

// Répertoire de stockage des fichiers constituant les lots
define('ACTES_BATCHES_UPLOAD_ROOT', '/tdt-workspace/actes/batchs');

// Liste des adresses de destinataires des notification commune écoutes les collectivités de l'instance
define('ACTES_COMMON_BROADCAST_EMAILS', 'toto@truc.fr'); 

// Restreindre ou non plusieurs demandes de classification par jour par collectivité (1 par jour si restreint)
define('ACTES_RESTRICT_CLASSIF_REQUEST_FREQUENCY', false);


//////////////////////////////////
///// Paramètre module Helios /////
/////////////////////////////////
define('HELIOS_FILES_ROOT', '/tdt-workspace/helios/');

// Répertoire de stockage des fichiers envoyés par les utilisateurs
define('HELIOS_FILES_UPLOAD_ROOT', '/tdt-workspace/helios/sending/');
// Repertoire de stockage des reponses
define('HELIOS_RESPONSES_ROOT', '/tdt-workspace/helios/response/');

/*
 * annuler la vérification de la connection de vpn du coté php. on va toujour accepter la postage avec le tempon.
 * 
define('HELIOS_SVN',"0"); // 1 pour connecter ver MIAT,0 ver simulateur
if (HELIOS_SVN)
{
	define('HELIOS_FTP_SERVER','91.121.157.192');
	define('HELIOS_FTP_PORT','2121');
	define('HELIOS_FTP_LOGIN','user');
	define('HELIOS_FTP_PASSWORD','password');
}
else
{
	define('HELIOS_FTP_SERVER','91.121.157.192');
	define('HELIOS_FTP_PORT','2121');
	define('HELIOS_FTP_LOGIN','user');
	define('HELIOS_FTP_PASSWORD','password');
}
*/

define('HELIOS_MAX_UPLOAD_SIZE',100*1024*1024);
define('HELIOS_GENERATED_FILE_PERMISSION','0644');

//////////////////////////////////
///// Paramètre module Etat Civil /////
/////////////////////////////////
// Répertoire de stockage des fichiers envoyés par les utilisateurs
define('ETAT_CIVIL_FILES_UPLOAD_ROOT', '/home/tedetis/dev/tdt-workspace/uploads/etat_civil');


//////////////////////////////
///// Paramètre module Mail  /////
//////////////////////////////////
// Répertoire de stockage des fichiers envoyés par les utilisateurs
define('MAIL_FILES_UPLOAD_ROOT', '/tdt-workspace/mail/');
define('MAIL_TEDETIS_FROM','tedetis-mailsec@adullact.fr');
define('IMAP_LOGIN','tedetis-mailsec');
define('IMAP_PASS','Yi7eequa');
define('IMAP_SERVER','mail.ntsys.fr');
define('MAIL_MESSAGE',"Vous avez reçu un courrier recommandé par S2LOW");
define('MAIL_TEXT',"Le serveur sécurisé TdT de l'ADULLACT vous informe qu'un  message recommandé mail avec d'éventuelles pièces attachées vous a été posté. Pour en prendre connaissance veuillez cliquer sur ce lien.");

require_once('config-static.php');




