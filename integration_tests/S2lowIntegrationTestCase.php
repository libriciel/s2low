<?php

namespace IntegrationTests;

use Exception;
use S2low\Enum\ModulePermission;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\PemCertificate;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2lowLegacy\Lib\SQLQuery;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class S2lowIntegrationTestCase extends WebTestCase
{
    protected SQLQuery $sqlQuery;
    private int $nextCreatedUserId = 1;
    protected PemCertificateFactory $pemCertificateFactory;
    protected PemCertificate $fixtureCertificate;

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
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['QUERY_STRING'] = '';
        $this->sqlQuery = new SQLQuery(DB_DATABASE_TEST);
        $this->sqlQuery->setCredential(DB_USER_TEST, DB_PASSWORD_TEST);
        $this->sqlQuery->setDatabaseHost(DB_HOST_TEST);
        $this->pemCertificateFactory = new PemCertificateFactory();

        $this->fixtureCertificate = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );
        $this->sqlQuery->exec(file_get_contents(__DIR__ . '/fixtures/s2low-test-init.sql'));
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

    private function getNextCreatedUserId(): int
    {
        return $this->nextCreatedUserId++;
    }

    /**
     * @throws Exception
     */
    public function createSuperAdminUser(string $certificatPem, string $certificatHash): void
    {
        $this->createUser(UserRole::SuperAdministrateur, $certificatPem, $certificatHash);
    }

    /**
     * @param string $certificatPem
     * @param string $certificatSansBegin
     * @return KernelBrowser
     */
    protected function createClientWithCertificat(string $certificatPem, string $certificatSansBegin): KernelBrowser
    {
        $serverCertificatEnvVar = [
            'SSL_CLIENT_VERIFY' => 'ssl_client_verify',
            'SSL_CLIENT_S_DN' => 'subject_dn',
            'SSL_CLIENT_I_DN' => 'issuer_dn',
            'SSL_CLIENT_CERT' => $certificatPem,
            'HTTP_ORG_S2LOW_FORWARD_X509_IDENTIFICATION' => $certificatSansBegin
        ];

        $this->addCertificatToServeurEnvironnement($serverCertificatEnvVar);

        self::ensureKernelShutdown();
        return static::createClient(
            [],
            $serverCertificatEnvVar
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
     * @param UserRole $role
     * @param ModulePermission $permissions
     * @return KernelBrowser
     * @throws Exception
     */
    protected function getAuthenticatedClientWithUserLoggedAs(
        UserRole $role,
        ModulePermission $permissions = ModulePermission::Modification
    ): KernelBrowser {
        $this->createUser(
            $role,
            $this->fixtureCertificate->getContent(),
            $this->fixtureCertificate->getHash(),
            $permissions
        );

        return $this->createClientWithCertificat(
            $this->fixtureCertificate->getContent(),
            $this->fixtureCertificate->getContentStrippedFromBegin()
        );
    }

    /**
     * @return KernelBrowser
     * @throws Exception
     */
    protected function getAuthenticatedClientWithSAdminUser(): KernelBrowser
    {
        return $this->getAuthenticatedClientWithUserLoggedAs(
            UserRole::SuperAdministrateur,
            ModulePermission::Modification
        );
    }

    private function createUser(
        UserRole $role,
        string $certificatPem,
        string $certificatHash,
        ModulePermission $permissions = ModulePermission::Modification
    ): int
    {
        $userId = $this->getNextCreatedUserId();

        $constMaximumUsersCreated = 100000;

        $userPermActeId = $userId;
        $userPermHeliosId = $userId + $constMaximumUsersCreated;
        $userPermMailId = $userId + $constMaximumUsersCreated * 2;

        $queryCreateUser = "INSERT INTO users VALUES ($userId, 'eric@sigmalis.com', 'test_subject', 'test_issuer', 'Pommateau', 'Eric', NULL, '$role->value', 1, 1, ?, NULL, NULL, NULL, 1, NULL, NULL, ?, ?)";
        $this->sqlQuery->query($queryCreateUser, [$certificatPem, $certificatPem, $certificatHash]);

        $queryAddModuleActePermission = "INSERT INTO users_perms VALUES ($userPermActeId, 1, $userId, '$permissions->value'); -- Permission RW sur le module Actes";
        $this->sqlQuery->query($queryAddModuleActePermission);

        $queryAddModuleHeliosPermission = "INSERT INTO users_perms VALUES ($userPermHeliosId, 2, $userId, '$permissions->value'); -- Permission RW sur le module Helios";
        $this->sqlQuery->query($queryAddModuleHeliosPermission);

        $queryAddModuleMailPermission = "INSERT INTO users_perms VALUES ($userPermMailId, 3, $userId, '$permissions->value'); -- Permission RW sur le module Mail";
        $this->sqlQuery->query($queryAddModuleMailPermission);

        return $userId;
    }

    protected function createUserWithDefaultCertificatAs(UserRole $role = UserRole::Utilisateur): int
    {
        return $this->createUser(
            $role,
            $this->fixtureCertificate->getContent(),
            $this->fixtureCertificate->getHash(),
            ModulePermission::Modification
        );
    }

    protected function getAuthenticatedClientAttachedToDefaultCertificat(): KernelBrowser
    {
        return $this->createClientWithCertificat(
            $this->fixtureCertificate->getContent(),
            $this->fixtureCertificate->getContentStrippedFromBegin()
        );
    }

    private function addCertificatToServeurEnvironnement(array $serverVariables): void
    {
        foreach ($serverVariables as $key => $value) {
            $_SERVER[$key] = $value;
        }
    }
}
