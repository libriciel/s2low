<?php

use PHPUnit\Framework\TestCase;

abstract class S2lowTestCase extends TestCase
{
    /**
     * @var SQLQuery
     */
    private static $sqlQueryStatic;

    private static $sqlContentStatic;

    protected $backupGlobalsBlacklist = array('sqlQuery');

    protected $sql_content;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
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

        ObjectInstancierFactory::setObjectInstancier(new ObjectInstancier());
        $this->getObjectInstancier()->__set('SQLQuery', $this->getSQLQuery());
        $this->getObjectInstancier()->set('helios_files_upload_root', "/tmp");
        $this->getObjectInstancier()->set('actes_files_upload_root', sys_get_temp_dir());

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
        $this->getObjectInstancier()->set('redis_mode', false);
        $this->getObjectInstancier()->set('redis_server', 'localhost');
        $this->getObjectInstancier()->set('redis_port', '');

        $get = array();
        $post = array();
        $request = array();
        $session = array();
        $server = array();

        $this->getObjectInstancier()->set('Environnement', new Environnement($get, $post, $request, $session, $server));
        $this->getObjectInstancier()->set("SessionWrapper", $this->getObjectInstancier()->get("Environnement")->session());
        $this->getObjectInstancier()->set("Monolog\Logger", new  Monolog\Logger('PHPUNIT'));
        $testHandler = new Monolog\Handler\TestHandler();
        $this->getObjectInstancier()->set("Monolog\Handler\TestHandler", $testHandler);
        $this->getObjectInstancier()->get("Monolog\Logger")->pushHandler($testHandler);

        $this->getObjectInstancier()->set('helios_ftp_server', 'server');
        $this->getObjectInstancier()->set('helios_ftp_passive_mode', 'HELIOS_FTP_PASSIVE_MODE');
        $this->getObjectInstancier()->set('helios_ftp_port', 'HELIOS_FTP_PORT');
        $this->getObjectInstancier()->set('helios_ftp_login', 'HELIOS_FTP_LOGIN');
        $this->getObjectInstancier()->set('helios_ftp_password', 'HELIOS_FTP_PASSWORD');
        $this->getObjectInstancier()->set('image_for_stamp', SITEROOT . "public.ssl/custom/images/bandeau-s2low-190.jpg");
    }

    public function getConnection()
    {
        if (! self::$sqlQueryStatic) {
            self::$sqlQueryStatic = new SQLQuery(DB_DATABASE_TEST);
            self::$sqlQueryStatic->setCredential(DB_USER_TEST, DB_PASSWORD_TEST);
            self::$sqlQueryStatic->setDatabaseHost(DB_HOST_TEST);
        }
    }


    protected function getSQLContent()
    {
        if (! self::$sqlContentStatic) {
            self::$sqlContentStatic = utf8_encode(file_get_contents(__DIR__ . "/s2low-test.sql")); // passage utf8
        }
        return self::$sqlContentStatic;
    }

    protected function getSetUpOperation()
    {
        return new \PHPUnit_Extensions_Database_Operation_Composite(array(
            \PHPUnit_Extensions_Database_Operation_Factory::DELETE_ALL(),
            \PHPUnit_Extensions_Database_Operation_Factory::INSERT()
        ));
    }

    /**
     * @return ObjectInstancier
     */
    public function getObjectInstancier()
    {
        return  ObjectInstancierFactory::getObjetInstancier();
    }

    /**
     * @return SQLQuery
     */
    public function getSQLQuery()
    {
        return self::$sqlQueryStatic;
    }

    protected function setServerInfo(array $server_info)
    {
        foreach ($server_info as $key => $value) {
            $this->getObjectInstancier()->get("Environnement")->server()->set($key, $value);
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
        $testHandler = $this->getObjectInstancier()->get("Monolog\Handler\TestHandler");
        return $testHandler->getRecords();
    }

    public function assertLogMessage($expected_message, $num_log = 0)
    {
        $this->assertEquals(
            $expected_message,
            $this->getLogRecords()[$num_log]['message']
        );
    }

    public function assertMatchesRegularExpressionLogMessage($expected_message, $num_log = 0)
    {
        $this->assertMatchesRegularExpression(
            $expected_message,
            $this->getLogRecords()[$num_log]['message']
        );
    }

    /** @deprecated  */
    public function setExpectedException($e, string $message)
    {
        $this->expectException($e);
        $this->expectExceptionMessage($message);
    }
    /** @deprecated  */
    public function noAssertion()
    {
        $this->assertTrue(true);
    }
}
