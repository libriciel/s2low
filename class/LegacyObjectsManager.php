<?php

namespace S2lowLegacy\Class;

use Psr\Log\LoggerInterface;
use RuntimeException;
use S2lowLegacy\Class\actes\ActesCloudStorable;
use S2lowLegacy\Class\actes\ActesImapProperties;
use S2lowLegacy\Class\actes\ActesMinistereProperties;
use S2lowLegacy\Class\actes\ActesPdf;
use S2lowLegacy\Class\actes\ActesPdfLegacy;
use S2lowLegacy\Class\actes\IActesPdf;
use S2lowLegacy\Class\helios\PESAcquitCloudStorable;
use S2lowLegacy\Class\helios\PESAllerCloudStorable;
use S2lowLegacy\Class\helios\PESRetourCloudStorable;
use S2lowLegacy\Class\mailsec\MailIncludedFilesCloudStorable;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\ObjectInstancierFactory;
use S2lowLegacy\Lib\OpenStackConfig;
use S2lowLegacy\Lib\OpenStackContainerStore;
use S2lowLegacy\Lib\OpenStackContainerWrapperFactory;
use S2lowLegacy\Lib\SessionWrapper;
use S2lowLegacy\Lib\SigTermHandler;
use S2lowLegacy\Lib\SQLQuery;
use Monolog\Handler\SymfonyMailerHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use S2lowLegacy\Mail\IMailHeader;
use S2lowLegacy\Mail\MailHeader;
use S2lowLegacy\Mail\MailHeaderLegacy;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class LegacyObjectsManager
{
    /**
     * @return array
     */
    public static function getLegacyObjectInstancier(): ObjectInstancier
    {

        if (!ObjectInstancierFactory::issetObjectInstancier()) {
            self::setLegacyObjectInstancier();
        }
        return ObjectInstancierFactory::getObjetInstancier();
    }

    /**
     * @return array
     */
    public static function setLegacyObjectInstancier(): void
    {
        if (TESTING_ENVIRONNEMENT) {
            $sqlQuery = new SQLQuery(
                DB_DATABASE_TEST,
                DB_HOST_TEST,
                DB_USER_TEST,
                DB_PASSWORD_TEST
            );
        } else {
            $sqlQuery = new SQLQuery(
                DB_DATABASE,
                DB_HOST,
                DB_USER,
                DB_PASSWORD
            );
        }

        $objectInstancier = new ObjectInstancier();
        ObjectInstancierFactory::setObjectInstancier($objectInstancier);

        $logger = new Logger("S2LOW");
        $logger->pushHandler(new StreamHandler(LOG_FILE, LOG_LEVEL));
        $logger->pushProcessor(function ($record) {
            $record['extra']['pid'] = getmypid();
            return $record;
        });

        $transport = Transport::fromDsn(MAILER_DSN);
        $mailer = new \Symfony\Component\Mailer\Mailer($transport);
        $email = (new Email())
            ->addFrom(TDT_FROM_EMAIL)
            ->addTo(EMAIL_ADMIN_TECHNIQUE);

        if (!TESTING_ENVIRONNEMENT) {
            $mailHandler = new SymfonyMailerHandler($mailer, $email, Logger::CRITICAL);
            $logger->pushHandler($mailHandler);
        }

        $objectInstancier->set(LoggerInterface::class, $logger);

        $objectInstancier->set('convert_api_logins_from_iso', CONVERT_API_LOGINS_FROM_ISO);

        $objectInstancier->{SQLQuery::class} = $sqlQuery;  //WARNING !! Pas certain de la manip

        $objectInstancier->set(Database::class, DatabasePool::getInstance());

        if (isset($_SESSION)) {
            $sessionWrapper = new SessionWrapper($_SESSION);
            $objectInstancier->set(SessionWrapper::class, $sessionWrapper);
            $environnement = new Environnement(
                $_GET,
                $_POST,
                $_REQUEST,
                $sessionWrapper,
                $_SERVER,
                CONVERT_API_LOGINS_FROM_ISO
            );
        } else {
            $session = array();
            $sessionWrapper = new SessionWrapper($session);
            $objectInstancier->set(SessionWrapper::class, $sessionWrapper);
            $environnement = new Environnement(
                $_GET,
                $_POST,
                $_REQUEST,
                $sessionWrapper,
                $_SERVER,
                CONVERT_API_LOGINS_FROM_ISO
            );
        }
        $objectInstancier->set(Environnement::class, $environnement);
        $objectInstancier->set("website_ssl", WEBSITE_SSL);
        $objectInstancier->set("website", WEBSITE);
        $objectInstancier->set("website_mail", WEBSITE_MAIL);

        $objectInstancier->set("use_prod_notifications", USE_PROD_NOTIFICATIONS);

        $objectInstancier->set('database_json_definition_filepath', __DIR__ . "/../db/s2low.sql.json");
        $objectInstancier->set('database_sql_definition_filepath', __DIR__ . "/../db/s2low.sql");

        $objectInstancier->set('helios_counter_file', HELIOS_COUNTER_FILE);
        $objectInstancier->set('helios_use_passtrans_as_default', HELIOS_USE_PASSTRANS_AS_DEFAULT);

        $objectInstancier->set('openstack_enable', OPENSTACK_ENABLE);

        $openStackConfigActes = new OpenStackConfig(
            ACTES_OPENSTACK_AUTHENTICATION_URL_V3,
            ACTES_OPENSTACK_USERNAME,
            ACTES_OPENSTACK_PASSWORD,
            ACTES_OPENSTACK_TENANT,
            ACTES_OPENSTACK_REGION,
            ACTES_OPENSTACK_SWIFT_CONTAINER_PREFIX
        );

        $openStackConfigHelios = new OpenStackConfig(
            HELIOS_OPENSTACK_AUTHENTICATION_URL_V3,
            HELIOS_OPENSTACK_USERNAME,
            HELIOS_OPENSTACK_PASSWORD,
            HELIOS_OPENSTACK_TENANT,
            HELIOS_OPENSTACK_REGION,
            HELIOS_OPENSTACK_SWIFT_CONTAINER_PREFIX
        );

        $openStackConfigHeliosAcquit = new OpenStackConfig(
            HELIOS_ACQUIT_OPENSTACK_AUTHENTICATION_URL_V3,
            HELIOS_ACQUIT_OPENSTACK_USERNAME,
            HELIOS_ACQUIT_OPENSTACK_PASSWORD,
            HELIOS_ACQUIT_OPENSTACK_TENANT,
            HELIOS_ACQUIT_OPENSTACK_REGION,
            HELIOS_ACQUIT_OPENSTACK_SWIFT_CONTAINER_PREFIX
        );

        $openStackConfigHeliosRetour = new OpenStackConfig(
            HELIOS_RETOUR_OPENSTACK_AUTHENTICATION_URL_V3,
            HELIOS_RETOUR_OPENSTACK_USERNAME,
            HELIOS_RETOUR_OPENSTACK_PASSWORD,
            HELIOS_RETOUR_OPENSTACK_TENANT,
            HELIOS_RETOUR_OPENSTACK_REGION,
            HELIOS_RETOUR_OPENSTACK_SWIFT_CONTAINER_PREFIX
        );

        $openStackConfigMailsec = new OpenStackConfig(
            MAILSEC_OPENSTACK_AUTHENTICATION_URL_V3,
            MAILSEC_OPENSTACK_USERNAME,
            MAILSEC_OPENSTACK_PASSWORD,
            MAILSEC_OPENSTACK_TENANT,
            MAILSEC_OPENSTACK_REGION,
            MAILSEC_OPENSTACK_SWIFT_CONTAINER_PREFIX
        );

        $openStackContainerWrapperFactory = new OpenStackContainerWrapperFactory($logger);
        $openStackContainerStore = new OpenStackContainerStore($openStackContainerWrapperFactory);

        $openStackContainerStore->addConfiguration(ActesCloudStorable::CONTAINER_NAME, $openStackConfigActes);
        $openStackContainerStore->addConfiguration(PESAllerCloudStorable::CONTAINER_NAME, $openStackConfigHelios);
        $openStackContainerStore->addConfiguration(PESAcquitCloudStorable::CONTAINER_NAME, $openStackConfigHeliosAcquit);
        $openStackContainerStore->addConfiguration(PESRetourCloudStorable::CONTAINER_NAME, $openStackConfigHeliosRetour);
        $openStackContainerStore->addConfiguration(MailIncludedFilesCloudStorable::CONTAINER_NAME, $openStackConfigMailsec);

        $objectInstancier->set(OpenStackContainerStore::class, $openStackContainerStore);


        $objectInstancier->set("helios_files_upload_root", HELIOS_FILES_UPLOAD_ROOT);
        $objectInstancier->set("repertoirePesAllerSansTransaction", HELIOS_PESALLER_SANSTRANSACTION);
        $objectInstancier->set("helios_responses_root", HELIOS_RESPONSES_ROOT);
        $objectInstancier->set("helios_responses_error_path", HELIOS_RESPONSES_ERROR_PATH);
        $objectInstancier->set("schema_pes_path", HELIOS_XSD_PATH);

        $objectInstancier->set("helios_responses_root", HELIOS_RESPONSES_ROOT);


        $objectInstancier->set("actes_files_upload_root", ACTES_FILES_UPLOAD_ROOT);
        $objectInstancier->set("repertoireActesEnveloppeSansTransaction", ACTES_ENVELOPPE_SANSTRANSACTION);
        $objectInstancier->set("actes_appli_trigramme", ACTES_APPLI_TRIGRAMME);
        $objectInstancier->set("actes_appli_quadrigramme", ACTES_APPLI_QUADRIGRAMME);

        $objectInstancier->set("actes_ministere_acronyme", ACTES_MINISTERE_ACRONYME);

        $objectInstancier->set("actes_dont_valid_signing_certificate", ACTES_DONT_VALID_SIGNING_CERTIFICATE);

        $actesImapProperties = new ActesImapProperties(
            ACTES_IMAP_HOST,
            ACTES_IMAP_PORT,
            ACTES_IMAP_LOGIN,
            ACTES_IMAP_PASSWORD,
            ACTES_IMAP_OPTIONS
        );
        $objectInstancier->set(ActesImapProperties::class, $actesImapProperties);

        $objectInstancier->set('actes_response_tmp_local_path', ACTES_RESPONSE_TMP_LOCAL_PATH);
        $objectInstancier->set('actes_response_error_path', ACTES_RESPONSE_ERROR_PATH);

        $objectInstancier->set('mail_files_upload_root', MAIL_FILES_UPLOAD_ROOT);
        $objectInstancier->set('mail_files_without_transac_dir', MAIL_FILES_WITHOUT_TRANSAC_DIR);

        $objectInstancier->set('pades_valid_url', PADES_VALID_URL);
        $objectInstancier->set('pdf_stamp_url', PDF_STAMP_URL);
        $objectInstancier->set('image_for_stamp', IMAGE_FOR_STAMP);

        $objectInstancier->set('rgs_validca_path', RGS_VALIDCA_PATH);

        $objectInstancier->set('beanstalkd_server', BEANSTAKLD_SERVER);
        $objectInstancier->set('beanstalkd_port', BEANSTAKLD_PORT);
        $objectInstancier->set('antivirus_command', ANTIVIRUS_COMMAND);
        $objectInstancier->set('openssl_path', OPENSSL_PATH);
        $objectInstancier->set('extended_validca_path', EXTENDED_VALIDCA_PATH);
        $objectInstancier->set('trustore_path', TRUSTSTORE_PATH);

        $objectInstancier->set('email_admin_technique', EMAIL_ADMIN_TECHNIQUE);
        $objectInstancier->set('tdt_from_email', TDT_FROM_EMAIL);
        $objectInstancier->set('log_level', LOG_LEVEL);

        $objectInstancier->set('redis_server', REDIS_SERVER);
        $objectInstancier->set('redis_port', REDIS_PORT);

        $objectInstancier->set('helios_ftp_response_tmp_local_path', HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH);

        $objectInstancier->set('helios_retention_fichiers_nb_jours', HELIOS_RETENTION_FICHIERS_NB_JOURS);

        $objectInstancier->set('old_timestamp_token_directory', OLD_TIMESTAMP_TOKEN_DIRECTORY);
        $objectInstancier->set('timestamp_token_retention_nb_days', TIMESTAMP_TOKEN_RETENTION_NB_DAYS);


        if (php_sapi_name() === 'cli') { // pcntl n'est actif qu'en mode CLI
            $objectInstancier->set(SigTermHandler::class, SigTermHandler::getInstance());
        }

        if (USE_LEGACY_BORDEREAU_MODEL) {
            $objectInstancier->set(
                IActesPdf::class,
                new ActesPdfLegacy(SITEROOT . "public.ssl/custom/images/bandeau-s2low-190.jpg")
            );
        } else {
            $objectInstancier->set(
                IActesPdf::class,
                new ActesPdf(SITEROOT . "public.ssl/custom/images/bandeau-s2low-190.jpg")
            );
        }

        if (USE_LEGACY_SECURE_MAIL_FIELDS) {
            $objectInstancier->set(
                IMailHeader::class,
                new MailHeaderLegacy(MAIL_MESSAGE, MAIL_TEDETIS_FROM, MAIL_SECURE_DESCRIPTION)
            );
        } else {
            $objectInstancier->set(
                IMailHeader::class,
                new MailHeader(MAIL_MESSAGE, MAIL_TEDETIS_FROM, MAIL_SECURE_DESCRIPTION)
            );
        }

        $objectInstancier->set('cachePath', "/var/run/htmlpurifier");

        $objectInstancier->set('html', '');
    }

    public static function resetObjectInstancier()
    {
        ObjectInstancierFactory::resetObjectInstancier();
    }

    /**
     * Utilisé pour l'injection de dépendances Symfony
     */
    public static function getObject(string $className)
    {
        if (!ObjectInstancierFactory::issetObjectInstancier()) {
            throw new RuntimeException('ObjectInstancier not set');
        }
        return ObjectInstancierFactory::getObjetInstancier()->get($className);
    }
}
