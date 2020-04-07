<?php

require_once __DIR__."/../vendor/autoload.php";

set_include_path( 	get_include_path() . PATH_SEPARATOR .
					__DIR__. "/../lib/" . PATH_SEPARATOR .
					__DIR__. "/../model/" . PATH_SEPARATOR .
					__DIR__. "/../controller/" . PATH_SEPARATOR .
					__DIR__ . "/../class/" . PATH_SEPARATOR . 
					__DIR__ . "/../class/actes"  . PATH_SEPARATOR . 
					__DIR__ . "/../class/helios"  . PATH_SEPARATOR . 
					__DIR__ . "/../class/mailsec". PATH_SEPARATOR

					);
					
if ( ! function_exists('s2low_autoload')) {
	function s2low_autoload($class_name) {
		@ $result = include($class_name . '.class.php');
		if ( ! $result ){
			return false;
		}
		return true;
	}
}

spl_autoload_register('s2low_autoload');


if (! function_exists('pcntl_async_signals')){
	function pcntl_async_signals($on) {}
}

if (! function_exists('pcntl_signal')){
	function pcntl_signal ($signo, $handler, $restart_syscalls = true) {}
}


if (! defined("SIGTERM")){
	define('SIGTERM',15);
}

if (! defined("SIGINT")){
	define('SIGINT',2);
}

require_once(__DIR__."/../config/config.php");

//A cause du chargement d'objet à partir de la session ... BEURK !
require_once(SITEROOT."/public.ssl/modules/mail/lib/Annuaire.class.php");

require_once(SITEROOT . '/class/include.class.php');


require_once(__DIR__."/../class/util.php");

$sqlQuery = new SQLQuery(DB_DATABASE);
$sqlQuery->setDatabaseHost(DB_HOST);
$sqlQuery->setCredential(DB_USER,DB_PASSWORD);
$sqlQuery->setClientEncoding(DB_CLIENT_ENCODING);

$objectInstancier = new ObjectInstancier();
ObjectInstancierFactory::setObjectInstancier($objectInstancier);

$logger = new Monolog\Logger("S2LOW");
$logger->pushHandler(new Monolog\Handler\StreamHandler(LOG_FILE, LOG_LEVEL));
$logger->pushProcessor(function ($record) {
	$record['extra']['pid'] = getmypid();
	return $record;
});

$mailHandler = new Monolog\Handler\NativeMailerHandler(
	[EMAIL_ADMIN_TECHNIQUE],
	"Erreur critique sur ".WEBSITE,
	TDT_FROM_EMAIL,
	Monolog\Logger::CRITICAL
);
$mailHandler->setEncoding('iso-8859-1');
$logger->pushHandler($mailHandler);


$objectInstancier->set('Monolog\Logger',$logger);

$objectInstancier->{'SQLQuery'} = $sqlQuery;

$objectInstancier->set('Database',DatabasePool::getInstance());

if (isset($_SESSION)) {
    $objectInstancier->set("SessionWrapper", new SessionWrapper($_SESSION));
    $environnement = new Environnement($_GET,$_POST,$_REQUEST,$_SESSION,$_SERVER);

} else {
    $session = array();
    $objectInstancier->set("SessionWrapper", new SessionWrapper($session));
    $environnement = new Environnement($_GET,$_POST,$_REQUEST,$session,$_SERVER);

}
$objectInstancier->set("Environnement",$environnement);
$objectInstancier->set("website_ssl",WEBSITE_SSL);
$objectInstancier->set("website",WEBSITE);

$objectInstancier->set('database_json_definition_filepath',__DIR__."/../db/s2low.sql.json");
$objectInstancier->set('database_sql_definition_filepath',__DIR__."/../db/s2low.sql");

$openStackConfigActes = new OpenStackConfig();
$openStackConfigActes->openstack_authentication_url_v3  = ACTES_OPENSTACK_AUTHENTICATION_URL_V3;
$openStackConfigActes->openstack_username = ACTES_OPENSTACK_USERNAME;
$openStackConfigActes->openstack_password = ACTES_OPENSTACK_PASSWORD;
$openStackConfigActes->openstack_tenant = ACTES_OPENSTACK_TENANT;
$openStackConfigActes->openstack_region = ACTES_OPENSTACK_REGION;
$openStackConfigActes->openstack_swift_container_prefix = ACTES_OPENSTACK_SWIFT_CONTAINER_PREFIX;


$openStackConfigHelios = new OpenStackConfig();
$openStackConfigHelios->openstack_authentication_url_v3  = HELIOS_OPENSTACK_AUTHENTICATION_URL_V3;
$openStackConfigHelios->openstack_username = HELIOS_OPENSTACK_USERNAME;
$openStackConfigHelios->openstack_password = HELIOS_OPENSTACK_PASSWORD;
$openStackConfigHelios->openstack_tenant = HELIOS_OPENSTACK_TENANT;
$openStackConfigHelios->openstack_region = HELIOS_OPENSTACK_REGION;
$openStackConfigHelios->openstack_swift_container_prefix = HELIOS_OPENSTACK_SWIFT_CONTAINER_PREFIX;


$openStackConfigHeliosAcquit = new OpenStackConfig();
$openStackConfigHeliosAcquit->openstack_authentication_url_v3  = HELIOS_ACQUIT_OPENSTACK_AUTHENTICATION_URL_V3;
$openStackConfigHeliosAcquit->openstack_username = HELIOS_ACQUIT_OPENSTACK_USERNAME;
$openStackConfigHeliosAcquit->openstack_password = HELIOS_ACQUIT_OPENSTACK_PASSWORD;
$openStackConfigHeliosAcquit->openstack_tenant = HELIOS_ACQUIT_OPENSTACK_TENANT;
$openStackConfigHeliosAcquit->openstack_region = HELIOS_ACQUIT_OPENSTACK_REGION;
$openStackConfigHeliosAcquit->openstack_swift_container_prefix = HELIOS_ACQUIT_OPENSTACK_SWIFT_CONTAINER_PREFIX;

$openStackConfigHeliosRetour = new OpenStackConfig();
$openStackConfigHeliosRetour->openstack_authentication_url_v3  = HELIOS_RETOUR_OPENSTACK_AUTHENTICATION_URL_V3;
$openStackConfigHeliosRetour->openstack_username = HELIOS_RETOUR_OPENSTACK_USERNAME;
$openStackConfigHeliosRetour->openstack_password = HELIOS_RETOUR_OPENSTACK_PASSWORD;
$openStackConfigHeliosRetour->openstack_tenant = HELIOS_RETOUR_OPENSTACK_TENANT;
$openStackConfigHeliosRetour->openstack_region = HELIOS_RETOUR_OPENSTACK_REGION;
$openStackConfigHeliosRetour->openstack_swift_container_prefix = HELIOS_RETOUR_OPENSTACK_SWIFT_CONTAINER_PREFIX;

$openStackConfigMailsec = new OpenStackConfig();
$openStackConfigMailsec->openstack_authentication_url_v3  = MAILSEC_OPENSTACK_AUTHENTICATION_URL_V3;
$openStackConfigMailsec->openstack_username = MAILSEC_OPENSTACK_USERNAME;
$openStackConfigMailsec->openstack_password = MAILSEC_OPENSTACK_PASSWORD;
$openStackConfigMailsec->openstack_tenant = MAILSEC_OPENSTACK_TENANT;
$openStackConfigMailsec->openstack_region = MAILSEC_OPENSTACK_REGION;
$openStackConfigMailsec->openstack_swift_container_prefix = MAILSEC_OPENSTACK_SWIFT_CONTAINER_PREFIX;

$openStackContainerWrapperFactory = new OpenStackContainerWrapperFactory();
$openStackContainerStore = new OpenStackContainerStore($openStackContainerWrapperFactory);

$openStackContainerStore->addConfiguration(ActesEnvelopeStorage::CONTAINER_NAME,$openStackConfigActes);
$openStackContainerStore->addConfiguration(PesAllerStorage::CONTAINER_NAME,$openStackConfigHelios);
$openStackContainerStore->addConfiguration(PESAcquitCloudStorage::CONTAINER_NAME,$openStackConfigHeliosAcquit);
$openStackContainerStore->addConfiguration(PESRetourCloudStorage::CONTAINER_NAME,$openStackConfigHeliosRetour);
$openStackContainerStore->addConfiguration(MailIncludedFilesCloudStorage::CONTAINER_NAME,$openStackConfigMailsec);

$objectInstancier->set(OpenStackContainerStore::class,$openStackContainerStore);


$objectInstancier->set("helios_files_upload_root",HELIOS_FILES_UPLOAD_ROOT);
$objectInstancier->set("helios_responses_root",HELIOS_RESPONSES_ROOT);
$objectInstancier->set("schema_pes_path",HELIOS_XSD_PATH);

$objectInstancier->set("helios_responses_root",HELIOS_RESPONSES_ROOT);


$objectInstancier->set("actes_files_upload_root",ACTES_FILES_UPLOAD_ROOT);
$objectInstancier->set("actes_appli_trigramme",ACTES_APPLI_TRIGRAMME);
$objectInstancier->set("actes_appli_quadrigramme",ACTES_APPLI_QUADRIGRAMME);

$objectInstancier->set("actes_ministere_acronyme",ACTES_MINISTERE_ACRONYME);

$objectInstancier->set("actes_dont_valid_signing_certificate",ACTES_DONT_VALID_SIGNING_CERTIFICATE);

$actesMinistereProperties = new ActesMinistereProperties();

$actesMinistereProperties->url = ACTES_MINISTERE_URL;
$actesMinistereProperties->authentification_type = ACTES_MINISTERE_AUTHENTICATION;

$actesMinistereProperties->login = ACTES_MINISTERE_LOGIN;
$actesMinistereProperties->password = ACTES_MINISTERE_PASSWORD;
$actesMinistereProperties->client_certificate = ACTES_MINISTERE_CERTIFICATE;
$actesMinistereProperties->client_certificate_key = ACTES_MINISTERE_CERTIFICATE_KEY;
$actesMinistereProperties->client_certificate_key_password = ACTES_MINISTERE_CERTIFICATE_KEY_PASS;
$actesMinistereProperties->server_certificate_path = ACTES_MINISTERE_SERVER_CERTIFICATE_PATH;
$objectInstancier->set('ActesMinistereProperties',$actesMinistereProperties);

$actesImapProperties = new ActesImapProperties();
$actesImapProperties->host = ACTES_IMAP_HOST;
$actesImapProperties->port = ACTES_IMAP_PORT;
$actesImapProperties->login = ACTES_IMAP_LOGIN;
$actesImapProperties->password = ACTES_IMAP_PASSWORD;
$objectInstancier->set('ActesImapProperties',$actesImapProperties);

$objectInstancier->set('actes_response_tmp_local_path',ACTES_RESPONSE_TMP_LOCAL_PATH);
$objectInstancier->set('actes_response_error_path',ACTES_RESPONSE_ERROR_PATH);
$objectInstancier->set('actes_type_pj_is_mandatory',ACTES_TYPE_PJ_IS_MANDATORY);


$objectInstancier->set('mail_files_upload_root',MAIL_FILES_UPLOAD_ROOT);

$objectInstancier->set('pades_valid_url',PADES_VALID_URL);
$objectInstancier->set('pdf_stamp_url',PDF_STAMP_URL);
$objectInstancier->set('image_for_stamp',IMAGE_FOR_STAMP);

$objectInstancier->set('rgs_validca_path',RGS_VALIDCA_PATH);

$objectInstancier->set('mode_beanstalkd',MODE_BEANSTALKD);
$objectInstancier->set('beanstalkd_server',BEANSTAKLD_SERVER);
$objectInstancier->set('beanstalkd_port',BEANSTAKLD_PORT);
$objectInstancier->set('antivirus_command',ANTIVIRUS_COMMAND);
$objectInstancier->set('openssl_path',OPENSSL_PATH);
$objectInstancier->set('extended_validca_path',EXTENDED_VALIDCA_PATH);

$objectInstancier->set('email_admin_technique',EMAIL_ADMIN_TECHNIQUE);
$objectInstancier->set('tdt_from_email',TDT_FROM_EMAIL);
$objectInstancier->set('log_level',LOG_LEVEL);

$objectInstancier->set('redis_mode',MODE_REDIS);
$objectInstancier->set('redis_server',REDIS_SERVER);
$objectInstancier->set('redis_port',REDIS_PORT);

$objectInstancier->set(SigTermHandler::class,SigTermHandler::getInstance());


$frontController = new FrontController($objectInstancier);

