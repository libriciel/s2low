<?php

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\SessionWrapper;
use S2lowLegacy\Lib\SQLQuery;

class TestEnvironmentManager
{
    private static $sqlContentStatic;

    /**
     * @var SQLQuery
     */
    private static $sqlQueryStatic;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        $this->getConnection();

        $this->getSQLQuery()->exec($this->getSQLContent());

        $this->getSQLQuery()->query("SELECT SETVAL('users_id_seq', (SELECT MAX(id)+1 FROM users))");
        $this->getSQLQuery()->query("SELECT SETVAL('authorities_id_seq', (SELECT MAX(id)+1 FROM authorities))");
        $this->getSQLQuery()->query("SELECT SETVAL('authority_groups_id_seq', (SELECT MAX(id)+1 FROM authority_groups))");


        $_GET = array();
        $_POST = array();
        $_SESSION = array();
        $_SERVER['SSL_CLIENT_VERIFY'] = "";
        $_SERVER['SSL_CLIENT_S_DN'] = "";
        $_SERVER['SSL_CLIENT_I_DN'] = "";
        $_SERVER['SSL_CLIENT_CERT'] = "";
        $_SERVER["QUERY_STRING"] = "";

        \S2lowLegacy\Lib\ObjectInstancierFactory::setObjectInstancier(new ObjectInstancier());
        $this->getObjectInstancier()->__set(SQLQuery::class, $this->getSQLQuery());
        $this->getObjectInstancier()->set('helios_files_upload_root', "/tmp");
        $this->getObjectInstancier()->set('actes_files_upload_root', sys_get_temp_dir());

        $this->getObjectInstancier()->set('use_prod_notifications', false);

        $this->getObjectInstancier()->set("openstack_enable", false);
        $this->getObjectInstancier()->set("openstack_authentication_url_v2", "");
        $this->getObjectInstancier()->set("openstack_username", "a");
        $this->getObjectInstancier()->set("openstack_password", "a");
        $this->getObjectInstancier()->set("openstack_tenant", "a");
        $this->getObjectInstancier()->set("openstack_region", "a");
        $this->getObjectInstancier()->set("openstack_swift_container_prefix", "a");
        $this->getObjectInstancier()->set("website", "http://s2low");
        $this->getObjectInstancier()->set("website_ssl", "https://s2low");
        $this->getObjectInstancier()->set("actes_appli_trigramme", "SLO");
        $this->getObjectInstancier()->set("actes_appli_quadrigramme", "EACT");
        $this->getObjectInstancier()->set("actes_ministere_acronyme", "MI");
        $this->getObjectInstancier()->set("actes_dont_valid_signing_certificate", false);
        $this->getObjectInstancier()->set("beanstalkd_server", false);
        $this->getObjectInstancier()->set("beanstalkd_port", false);
        $this->getObjectInstancier()->set('antivirus_command', 'ls');
        $this->getObjectInstancier()->set('pades_valid_url', 'https://s2low');
        $this->getObjectInstancier()->set('openssl_path', OPENSSL_PATH);
        $this->getObjectInstancier()->set('rgs_validca_path', RGS_VALIDCA_PATH);
        $this->getObjectInstancier()->set('extended_validca_path', EXTENDED_VALIDCA_PATH);
        $this->getObjectInstancier()->set('trustore_path', TRUSTSTORE_PATH);
        $this->getObjectInstancier()->set('schema_pes_path', HELIOS_XSD_PATH);
        $this->getObjectInstancier()->set('redis_server', 'localhost');
        $this->getObjectInstancier()->set('redis_port', 6379);

        $get = array();
        $post = array();
        $request = array();
        $session = array();
        $server = array();

        $this->getObjectInstancier()->set(Environnement::class, new Environnement($get, $post, $request, $session, $server, false));
        $this->getObjectInstancier()->set(SessionWrapper::class, $this->getObjectInstancier()->get(Environnement::class)->session());
        $monologLogger = new  Logger('PHPUNIT');
        $this->getObjectInstancier()->set(Logger::class, $monologLogger);
        $testHandler = new TestHandler();
        $this->getObjectInstancier()->set(TestHandler::class, $testHandler);
        $this->getObjectInstancier()->get(Logger::class)->pushHandler($testHandler);

        $this->getObjectInstancier()->set('convert_api_logins_from_iso', false);

        // WARNING : PAS SUR DE LA MANIP
        $this->getObjectInstancier()->set(S2lowLogger::class, new  S2lowLogger($monologLogger));

        $this->getObjectInstancier()->set('image_for_stamp', SITEROOT . 'public.ssl/custom/images/bandeau-s2low-190.jpg');
        $this->getObjectInstancier()->set('repertoirePesAllerSansTransaction', '');
        $this->getObjectInstancier()->set('mail_files_upload_root', '');
        $this->getObjectInstancier()->set('mail_files_without_transac_dir', '');
        $tmpFolder = new TmpFolder();
        $this->getObjectInstancier()->set('repertoireActesEnveloppeSansTransaction', $tmpFolder->create());
    }

    public function getConnection()
    {
        if (!self::$sqlQueryStatic) {
            self::$sqlQueryStatic = new SQLQuery(
                DB_DATABASE_TEST,
                DB_HOST_TEST,
                DB_USER_TEST,
                DB_PASSWORD_TEST
            );
        }
    }

    protected function getSQLContent()
    {
        if (! self::$sqlContentStatic) {
            self::$sqlContentStatic = file_get_contents(__DIR__ . "/s2low-test.sql"); // passage utf8
        }
        return self::$sqlContentStatic;
    }

    /**
     * @return ObjectInstancier
     */
    public function getObjectInstancier()
    {
        return  \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier();
    }

    /**
     * @return SQLQuery
     */
    public function getSQLQuery()
    {
        return self::$sqlQueryStatic;
    }

    public function setServerInfo(array $server_info)
    {
        foreach ($server_info as $key => $value) {
            $this->getObjectInstancier()->get(Environnement::class)->server()->set($key, $value);
        }
    }

    public function setSuperAdminAuthentication()
    {
        $this->setServerInfo([
            'SSL_CLIENT_VERIFY' => "SUCCESS",
            'SSL_CLIENT_S_DN' => "test_subject",
            'SSL_CLIENT_I_DN' => "test_issuer",
            'TESTING_CERTIFICATE_HASH' => "Q1pUbEb5DK53BkYf0arDl/3zl5U=",
        ]);
    }

    public function setAdminGroupAuthentication()
    {
        $this->setServerInfo([
            'SSL_CLIENT_VERIFY' => "SUCCESS",
            'SSL_CLIENT_S_DN' => "admin_groupe",
            'SSL_CLIENT_I_DN' => "admin_groupe",
            'TESTING_CERTIFICATE_HASH' => "hash_admin_groupe",
        ]);
    }

    public function setAdminGroup2Authentication()
    {
        $this->setServerInfo([
            'SSL_CLIENT_VERIFY' => "SUCCESS",
            'SSL_CLIENT_S_DN' => "admin_groupe2",
            'SSL_CLIENT_I_DN' => "admin_groupe2",
            'TESTING_CERTIFICATE_HASH' => "hash_admin_groupe2",
        ]);
    }


    public function setAdminColAuthentication()
    {
        $this->setServerInfo([
            'SSL_CLIENT_VERIFY' => "SUCCESS",
            'SSL_CLIENT_S_DN' => "admin_col1",
            'SSL_CLIENT_I_DN' => "admin_col1",
            'TESTING_CERTIFICATE_HASH' => "admin_col1",
        ]);
    }

    public function setAdminCol2Authentication()
    {
        $this->setServerInfo([
            'SSL_CLIENT_VERIFY' => "SUCCESS",
            'SSL_CLIENT_S_DN' => "admin_col2",
            'SSL_CLIENT_I_DN' => "admin_col2",
            'TESTING_CERTIFICATE_HASH' => "hash_admin_col2",
        ]);
    }

    public function setUserAuthentification()
    {
        $this->setServerInfo([
            'SSL_CLIENT_VERIFY' => "SUCCESS",
            'SSL_CLIENT_S_DN' => "user_col1",
            'SSL_CLIENT_I_DN' => "user_col1",
            'TESTING_CERTIFICATE_HASH' => "hash_user_col1",
        ]);
    }

    public function getLogRecords()
    {
        $testHandler = $this->getObjectInstancier()->get(TestHandler::class);
        return $testHandler->getRecords();
    }

    public function setArchAuthentification(): void
    {
        $this->setServerInfo([
            'SSL_CLIENT_VERIFY' => "SUCCESS",
            'SSL_CLIENT_S_DN' => "adullact_arch",
            'SSL_CLIENT_I_DN' => "adullact_arch",
            'TESTING_CERTIFICATE_HASH' => "hash_adullact_arch",
        ]);
    }
}
