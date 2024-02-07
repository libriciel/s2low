<?php

declare(strict_types=1);

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\SessionWrapper;
use S2lowLegacy\Lib\SQLQuery;

class TestEnvironmentManager
{
    /**
     * @var string
     */
    private static $sqlContentStatic;

    /**
     * @var SQLQuery
     */
    private static $sqlQueryStatic;

    public function setUp()
    {
        // A priori nécessaire, mais je ne comprend pas pquoi !
        // Sinon, on a des messages de type "trop d'utilisateurs connectés"
        $this->getConnection();

        $this->getSQLQuery()->exec($this->getSQLContent());

        $this->getSQLQuery()->query("SELECT SETVAL('users_id_seq', (SELECT MAX(id)+1 FROM users))");
        $this->getSQLQuery()->query("SELECT SETVAL('authorities_id_seq', (SELECT MAX(id)+1 FROM authorities))");
        $this->getSQLQuery()->query("SELECT SETVAL('authority_groups_id_seq', (SELECT MAX(id)+1 FROM authority_groups))");

        $this->getObjectInstancier()->__set(SQLQuery::class, $this->getSQLQuery());
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

    /**
     * @return array
     */
    public function getLogRecords(): array
    {
        /** @var  TestHandler $testHandler */
        $testHandler = $this->getObjectInstancier()->get(TestHandler::class);
        return $testHandler->getRecords();
    }
}
