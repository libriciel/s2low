<?php

namespace IntegrationTests;

use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2lowLegacy\Lib\SQLQuery;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class S2lowIntegrationTestCase extends WebTestCase
{
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
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['QUERY_STRING'] = '';
        $this->sqlQuery = new SQLQuery(DB_DATABASE_TEST);
        $this->sqlQuery->setCredential(DB_USER_TEST, DB_PASSWORD_TEST);
        $this->sqlQuery->setDatabaseHost(DB_HOST_TEST);
        $this->pemCertificateFactory = new PemCertificateFactory();
        $this->sqlQuery->exec(file_get_contents(__DIR__ . '/fixtures/s2low-test-init.sql'));
        parent::setUp();
    }

    protected function tearDown(): void
    {
        self::ensureKernelShutdown();
        LegacyObjectsManager::resetObjectInstancier(); //Evite les interactions entre tests via
        // L'objectInstancier.
        // Normalement on ne devrait pas modifier l'ObjectInstancier pour les tests d'intégration
        // Mais on ne sait jamais ...
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['QUERY_STRING'] = '';
        // Evite le message postgres phpunit désolé, trop de clients sont déjà connectés
        $this->sqlQuery->disconnect();
        parent::tearDown();
    }

    /**
     * @throws \Exception
     */
    public function setUpUserInDB(string $certificatPem, string $certificatHash): void
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
     * @param string $certificatPem
     * @param string $certificatSansBegin
     * @return \Symfony\Bundle\FrameworkBundle\KernelBrowser
     */
    protected function setUpUserCertInServer(string $certificatPem, string $certificatSansBegin): KernelBrowser
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
        self::ensureKernelShutdown();
        return static::createClient(
            [],
            $serverVariables
        );
    }

    /**
     * @return SQLQuery
     */
    public function getSQLQuery(): SQLQuery
    {
        return $this->sqlQuery;
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\KernelBrowser
     * @throws \Exception
     */
    protected function setUpUser(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserInDB($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        return $client;
    }
}
