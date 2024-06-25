<?php

namespace IntegrationTests;

use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\ObjectInstancierFactory;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2lowLegacy\Lib\SQLQuery;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class S2lowIntegrationTestCase extends WebTestCase
{
    /** @var SQLQuery */
    protected SQLQuery $sqlQuery;
    protected PemCertificateFactory $pemCertificateFactory;

    /**
     * @param int|string $dataName
     *
     * @internal This method is not covered by the backward compatibility promise for PHPUnit
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        LegacyObjectsManager::resetObjectInstancier();
        $_SESSION = [];
        ObjectInstancierFactory::setObjectInstancier(new ObjectInstancier());    //DatabasePool utilise ObjectInstancier
        $this->sqlQuery = new SQLQuery(DB_DATABASE_TEST);            // On en crée un le temps de MàJ la BDD
        $this->sqlQuery->setCredential(DB_USER_TEST, DB_PASSWORD_TEST); // On le ressettera ensuite
        $this->sqlQuery->setDatabaseHost(DB_HOST_TEST);
        ObjectInstancierFactory::getObjetInstancier()->set(SQLQuery::class, $this->sqlQuery);
        $this->pemCertificateFactory = new PemCertificateFactory();
        $this->sqlQuery->exec(file_get_contents(__DIR__ . '/fixtures/s2low-test-init.sql'));
        ObjectInstancierFactory::resetObjectInstancier();    //DatabasePool utilise ObjectInstancier
    }

    /**
     * @throws \Exception
     */
    public function SuperAdmin(string $certificatPem, string $certificatHash): void
    {
        $sql = "INSERT INTO users VALUES (1, 'eric@sigmalis.com', 'test_subject', 'test_issuer', 'Pommateau', 'Eric', NULL, 'SADM', 1, 1, ?, NULL, NULL, NULL, 1, NULL, NULL, ?, ?)";
        $this->sqlQuery->query($sql, [$certificatPem, $certificatPem, $certificatHash]);

        $sql1 = "INSERT INTO users_perms VALUES (64395, 1, 1, 'RW'); -- Permission RW sur le module Actes";
        $this->sqlQuery->query($sql1);
        $sql2 = "INSERT INTO users_perms VALUES (64396, 2, 1, 'RW'); -- Permission RW sur le module Helios";
        $this->sqlQuery->query($sql2);
        $sql3 = "INSERT INTO users_perms VALUES (64397, 3, 1, 'RW'); -- Permission RW sur le module Mail";
        $this->sqlQuery->query($sql3);
    }

    /**
     * @throws \Exception
     */
    public function setUpUserWithNoPermissions(string $certificatPem, string $certificatHash): void
    {
        $sql = "INSERT INTO users VALUES (1, 'eric@sigmalis.com', 'test_subject', 'test_issuer', 'Pommateau', 'Eric', NULL, 'USER', 1, 1, ?, NULL, NULL, NULL, 1, NULL, NULL, ?, ?)";
        $this->sqlQuery->query($sql, [$certificatPem, $certificatPem, $certificatHash]);

        $sql1 = "DELETE FROM users_perms WHERE user_id=1; -- Suppression des permissions sur tous les modules";
        $this->sqlQuery->query($sql1);
    }

    /**
     * @param string $certificatPem
     * @param string $certificatSansBegin
     * @return \Symfony\Bundle\FrameworkBundle\KernelBrowser
     */
    protected function setUpClient(string $certificatPem, string $certificatSansBegin): KernelBrowser
    {
        $serverVariables = [
            'SSL_CLIENT_VERIFY' => 'ssl_client_verify',
            'SSL_CLIENT_S_DN' => 'subject_dn',
            'SSL_CLIENT_I_DN' => 'issuer_dn',
            'SSL_CLIENT_CERT' => $certificatPem,
            'HTTP_ORG_S2LOW_FORWARD_X509_IDENTIFICATION' => $certificatSansBegin

        ];
        foreach ($serverVariables as $key => $value) {
            $_SERVER[$key] = $value;         // Le client Symfony ne set pas la session, utilisée par l'appli...
        }
        return static::createClient(
            [],
            $serverVariables
        );
    }
}
