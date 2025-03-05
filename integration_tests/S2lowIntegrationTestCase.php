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
    private int $nextCreatedUserId = 1;
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
        $this->setUpWithoutDeletingObjectInstancier();
        ObjectInstancierFactory::resetObjectInstancier();    //DatabasePool utilise ObjectInstancier
    }

    protected function tearDown(): void
    {
        // Evite le message postgres phpunit désolé, trop de clients sont déjà connectés
        // TODO : ce disconnect serait-il nécessaire ailleurs ?
        $this->sqlQuery->disconnect();
        parent::tearDown();
    }

    private function getNextCreatedUserId(): int
    {
        return $this->nextCreatedUserId++;
    }

    /**
     * @throws \Exception
     */
    public function setUpUserInDB(string $certificatPem, string $certificatHash): void
    {
        $this->createUserAs('SADM', $certificatPem, $certificatHash);
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
        return $this->setUpUserAs('SADM');
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\KernelBrowser
     * @throws \Exception
     */
    protected function setUpUserAs(string $role): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->createUserAs($role, $certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpUserCertInServer(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );

        ObjectInstancierFactory::resetObjectInstancier();
        return $client;
    }

    /**
     * @return void
     */
    protected function setUpWithoutDeletingObjectInstancier(): void
    {
        parent::setUp();
        LegacyObjectsManager::resetObjectInstancier();
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        //$_SERVER = [];
        $_SERVER['QUERY_STRING'] = '';
        ObjectInstancierFactory::setObjectInstancier(new ObjectInstancier());    //DatabasePool utilise ObjectInstancier
        $this->sqlQuery = new SQLQuery(DB_DATABASE_TEST);            // On en crée un le temps de MàJ la BDD
        $this->sqlQuery->setCredential(DB_USER_TEST, DB_PASSWORD_TEST); // On le ressettera ensuite
        $this->sqlQuery->setDatabaseHost(DB_HOST_TEST);
        ObjectInstancierFactory::getObjetInstancier()->set(SQLQuery::class, $this->sqlQuery);
        $this->pemCertificateFactory = new PemCertificateFactory();
        $this->sqlQuery->exec(file_get_contents(__DIR__ . '/fixtures/s2low-test-init.sql'));
    }

    private function createUserAs(string $role, string $certificatPem, string $certificatHash): void
    {
        $userId = $this->getNextCreatedUserId();

        $constMaximumUsersCreated = 100000;

        $userPermActeId = $userId;
        $userPermHeliosId = $userId + $constMaximumUsersCreated;
        $userPermMailId = $userId + $constMaximumUsersCreated * 2;

        $sql = "INSERT INTO users VALUES ($userId, 'eric@sigmalis.com', 'test_subject', 'test_issuer', 'Pommateau', 'Eric', NULL, '$role', 1, 1, ?, NULL, NULL, NULL, 1, NULL, NULL, ?, ?)";
        $this->sqlQuery->query($sql, [$certificatPem, $certificatPem, $certificatHash]);

        $sql1 = "INSERT INTO users_perms VALUES ($userPermActeId, 1, $userId, 'RW'); -- Permission RW sur le module Actes";
        $this->sqlQuery->query($sql1);
        $sql2 = "INSERT INTO users_perms VALUES ($userPermHeliosId, 2, $userId, 'RW'); -- Permission RW sur le module Helios";
        $this->sqlQuery->query($sql2);
        $sql3 = "INSERT INTO users_perms VALUES ($userPermMailId, 3, $userId, 'RW'); -- Permission RW sur le module Mail";
        $this->sqlQuery->query($sql3);
    }

    /**
     * @throws \Exception
     */
    protected function getAuthenticatedClientWithUserLoggedAs(string $role): KernelBrowser
    {
        return $this->setUpUserAs($role);
    }
}
