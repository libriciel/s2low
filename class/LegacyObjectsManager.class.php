<?php

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
            $sqlQuery = new SQLQuery(DB_DATABASE_TEST);
            $sqlQuery->setCredential(DB_USER_TEST, DB_PASSWORD_TEST);
            $sqlQuery->setDatabaseHost(DB_HOST_TEST);
        } else {
            $sqlQuery = new SQLQuery(DB_DATABASE);
            $sqlQuery->setDatabaseHost(DB_HOST);
            $sqlQuery->setCredential(DB_USER, DB_PASSWORD);
        }

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
            "Erreur critique sur " . WEBSITE,
            TDT_FROM_EMAIL,
            Monolog\Logger::CRITICAL
        );
        $mailHandler->setEncoding('iso-8859-1');
        $logger->pushHandler($mailHandler);


        $objectInstancier->set('Monolog\Logger', $logger);

        $objectInstancier->{'SQLQuery'} = $sqlQuery;

        $objectInstancier->set('Database', DatabasePool::getInstance());

        if (isset($_SESSION)) {
            $objectInstancier->set("SessionWrapper", new SessionWrapper($_SESSION));
            $environnement = new Environnement($_GET, $_POST, $_REQUEST, $_SESSION, $_SERVER);
        } else {
            $session = array();
            $objectInstancier->set("SessionWrapper", new SessionWrapper($session));
            $environnement = new Environnement($_GET, $_POST, $_REQUEST, $session, $_SERVER);
        }
        $objectInstancier->set("Environnement", $environnement);
        $objectInstancier->set("website_ssl", WEBSITE_SSL);
        $objectInstancier->set("website", WEBSITE);

        $objectInstancier->set('database_json_definition_filepath', __DIR__ . "/../db/s2low.sql.json");
        $objectInstancier->set('database_sql_definition_filepath', __DIR__ . "/../db/s2low.sql");

        $openStackConfigActes = new OpenStackConfig();
        $openStackConfigActes->openstack_authentication_url_v3 = ACTES_OPENSTACK_AUTHENTICATION_URL_V3;
        $openStackConfigActes->openstack_username = ACTES_OPENSTACK_USERNAME;
        $openStackConfigActes->openstack_password = ACTES_OPENSTACK_PASSWORD;
        $openStackConfigActes->openstack_tenant = ACTES_OPENSTACK_TENANT;
        $openStackConfigActes->openstack_region = ACTES_OPENSTACK_REGION;
        $openStackConfigActes->openstack_swift_container_prefix = ACTES_OPENSTACK_SWIFT_CONTAINER_PREFIX;


        $openStackConfigHelios = new OpenStackConfig();
        $openStackConfigHelios->openstack_authentication_url_v3 = HELIOS_OPENSTACK_AUTHENTICATION_URL_V3;
        $openStackConfigHelios->openstack_username = HELIOS_OPENSTACK_USERNAME;
        $openStackConfigHelios->openstack_password = HELIOS_OPENSTACK_PASSWORD;
        $openStackConfigHelios->openstack_tenant = HELIOS_OPENSTACK_TENANT;
        $openStackConfigHelios->openstack_region = HELIOS_OPENSTACK_REGION;
        $openStackConfigHelios->openstack_swift_container_prefix = HELIOS_OPENSTACK_SWIFT_CONTAINER_PREFIX;


        $openStackConfigHeliosAcquit = new OpenStackConfig();
        $openStackConfigHeliosAcquit->openstack_authentication_url_v3 = HELIOS_ACQUIT_OPENSTACK_AUTHENTICATION_URL_V3;
        $openStackConfigHeliosAcquit->openstack_username = HELIOS_ACQUIT_OPENSTACK_USERNAME;
        $openStackConfigHeliosAcquit->openstack_password = HELIOS_ACQUIT_OPENSTACK_PASSWORD;
        $openStackConfigHeliosAcquit->openstack_tenant = HELIOS_ACQUIT_OPENSTACK_TENANT;
        $openStackConfigHeliosAcquit->openstack_region = HELIOS_ACQUIT_OPENSTACK_REGION;
        $openStackConfigHeliosAcquit->openstack_swift_container_prefix = HELIOS_ACQUIT_OPENSTACK_SWIFT_CONTAINER_PREFIX;

        $openStackConfigHeliosRetour = new OpenStackConfig();
        $openStackConfigHeliosRetour->openstack_authentication_url_v3 = HELIOS_RETOUR_OPENSTACK_AUTHENTICATION_URL_V3;
        $openStackConfigHeliosRetour->openstack_username = HELIOS_RETOUR_OPENSTACK_USERNAME;
        $openStackConfigHeliosRetour->openstack_password = HELIOS_RETOUR_OPENSTACK_PASSWORD;
        $openStackConfigHeliosRetour->openstack_tenant = HELIOS_RETOUR_OPENSTACK_TENANT;
        $openStackConfigHeliosRetour->openstack_region = HELIOS_RETOUR_OPENSTACK_REGION;
        $openStackConfigHeliosRetour->openstack_swift_container_prefix = HELIOS_RETOUR_OPENSTACK_SWIFT_CONTAINER_PREFIX;

        $openStackConfigMailsec = new OpenStackConfig();
        $openStackConfigMailsec->openstack_authentication_url_v3 = MAILSEC_OPENSTACK_AUTHENTICATION_URL_V3;
        $openStackConfigMailsec->openstack_username = MAILSEC_OPENSTACK_USERNAME;
        $openStackConfigMailsec->openstack_password = MAILSEC_OPENSTACK_PASSWORD;
        $openStackConfigMailsec->openstack_tenant = MAILSEC_OPENSTACK_TENANT;
        $openStackConfigMailsec->openstack_region = MAILSEC_OPENSTACK_REGION;
        $openStackConfigMailsec->openstack_swift_container_prefix = MAILSEC_OPENSTACK_SWIFT_CONTAINER_PREFIX;

        $openStackContainerWrapperFactory = new OpenStackContainerWrapperFactory($logger);
        $openStackContainerStore = new OpenStackContainerStore($openStackContainerWrapperFactory);

        $openStackContainerStore->addConfiguration(ActesEnvelopeStorage::CONTAINER_NAME, $openStackConfigActes);
        $openStackContainerStore->addConfiguration(PesAllerStorage::CONTAINER_NAME, $openStackConfigHelios);
        $openStackContainerStore->addConfiguration(PESAcquitCloudStorage::CONTAINER_NAME, $openStackConfigHeliosAcquit);
        $openStackContainerStore->addConfiguration(PESRetourCloudStorage::CONTAINER_NAME, $openStackConfigHeliosRetour);
        $openStackContainerStore->addConfiguration(MailIncludedFilesCloudStorage::CONTAINER_NAME, $openStackConfigMailsec);

        $objectInstancier->set(OpenStackContainerStore::class, $openStackContainerStore);


        $objectInstancier->set("helios_files_upload_root", HELIOS_FILES_UPLOAD_ROOT);
        $objectInstancier->set("repertoirePesAllerSansTransaction", HELIOS_PESALLER_SANSTRANSACTION);
        $objectInstancier->set("helios_responses_root", HELIOS_RESPONSES_ROOT);
        $objectInstancier->set("schema_pes_path", HELIOS_XSD_PATH);

        $objectInstancier->set("helios_responses_root", HELIOS_RESPONSES_ROOT);


        $objectInstancier->set("actes_files_upload_root", ACTES_FILES_UPLOAD_ROOT);
        $objectInstancier->set("actes_appli_trigramme", ACTES_APPLI_TRIGRAMME);
        $objectInstancier->set("actes_appli_quadrigramme", ACTES_APPLI_QUADRIGRAMME);

        $objectInstancier->set("actes_ministere_acronyme", ACTES_MINISTERE_ACRONYME);

        $objectInstancier->set("actes_dont_valid_signing_certificate", ACTES_DONT_VALID_SIGNING_CERTIFICATE);

        $actesMinistereProperties = new ActesMinistereProperties();

        $actesMinistereProperties->url = ACTES_MINISTERE_URL;
        $actesMinistereProperties->authentification_type = ACTES_MINISTERE_AUTHENTICATION;

        $actesMinistereProperties->login = ACTES_MINISTERE_LOGIN;
        $actesMinistereProperties->password = ACTES_MINISTERE_PASSWORD;
        $actesMinistereProperties->client_certificate = ACTES_MINISTERE_CERTIFICATE;
        $actesMinistereProperties->client_certificate_key = ACTES_MINISTERE_CERTIFICATE_KEY;
        $actesMinistereProperties->client_certificate_key_password = ACTES_MINISTERE_CERTIFICATE_KEY_PASS;
        $actesMinistereProperties->server_certificate_path = ACTES_MINISTERE_SERVER_CERTIFICATE_PATH;
        $objectInstancier->set('ActesMinistereProperties', $actesMinistereProperties);

        $actesImapProperties = new ActesImapProperties();
        $actesImapProperties->host = ACTES_IMAP_HOST;
        $actesImapProperties->port = ACTES_IMAP_PORT;
        $actesImapProperties->login = ACTES_IMAP_LOGIN;
        $actesImapProperties->password = ACTES_IMAP_PASSWORD;
        $objectInstancier->set('ActesImapProperties', $actesImapProperties);

        $objectInstancier->set('actes_response_tmp_local_path', ACTES_RESPONSE_TMP_LOCAL_PATH);
        $objectInstancier->set('actes_response_error_path', ACTES_RESPONSE_ERROR_PATH);

        $objectInstancier->set('mail_files_upload_root', MAIL_FILES_UPLOAD_ROOT);

        $objectInstancier->set('pades_valid_url', PADES_VALID_URL);
        $objectInstancier->set('pdf_stamp_url', PDF_STAMP_URL);
        $objectInstancier->set('image_for_stamp', IMAGE_FOR_STAMP);

        $objectInstancier->set('rgs_validca_path', RGS_VALIDCA_PATH);

        $objectInstancier->set('beanstalkd_server', BEANSTAKLD_SERVER);
        $objectInstancier->set('beanstalkd_port', BEANSTAKLD_PORT);
        $objectInstancier->set('antivirus_command', ANTIVIRUS_COMMAND);
        $objectInstancier->set('openssl_path', OPENSSL_PATH);
        $objectInstancier->set('extended_validca_path', EXTENDED_VALIDCA_PATH);

        $objectInstancier->set('email_admin_technique', EMAIL_ADMIN_TECHNIQUE);
        $objectInstancier->set('tdt_from_email', TDT_FROM_EMAIL);
        $objectInstancier->set('log_level', LOG_LEVEL);

        $objectInstancier->set('redis_server', REDIS_SERVER);
        $objectInstancier->set('redis_port', REDIS_PORT);

        $objectInstancier->set('helios_ftp_server', HELIOS_FTP_SERVER);
        $objectInstancier->set('helios_ftp_passive_mode', HELIOS_FTP_PASSIVE_MODE);
        $objectInstancier->set('helios_ftp_passtrans_mode', HELIOS_FTP_PASSTRANS_MODE);
        $objectInstancier->set('helios_ftp_p_appli', HELIOS_FTP_P_APPLI);
        $objectInstancier->set('helios_ftp_port', HELIOS_FTP_PORT);
        $objectInstancier->set('helios_ftp_login', HELIOS_FTP_LOGIN);
        $objectInstancier->set('helios_ftp_password', HELIOS_FTP_PASSWORD);
        $objectInstancier->set('helios_ftp_response_server_path', HELIOS_FTP_RESPONSE_SERVER_PATH);
        $objectInstancier->set('helios_ftp_response_tmp_local_path', HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH);
        $objectInstancier->set('helios_sending_destination', HELIOS_SENDING_DESTINATION);
        $objectInstancier->set('helios_sending_mode_demo', HELIOS_SENDING_MODE_DEMO);

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
                MailHeader::class,
                new MailHeaderLegacy(MAIL_MESSAGE, MAIL_TEDETIS_FROM, MAIL_SECURE_DESCRIPTION)
            );
        } else {
            $objectInstancier->set(
                MailHeader::class,
                new MailHeader(MAIL_MESSAGE, MAIL_TEDETIS_FROM, MAIL_SECURE_DESCRIPTION)
            );
        }

        $objectInstancier->set('html', '');
    }

    public static function resetObjectInstancier()
    {
        ObjectInstancierFactory::resetObjectInstancier();
    }
}
