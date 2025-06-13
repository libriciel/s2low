<?php

namespace IntegrationTests;

use Exception;
use org\bovigo\vfs\vfsStream;
use PHPUnit\LoginUtilsForTestSetup;
use S2low\Enum\ModulePermission;
use S2low\Enum\UserRole;
use S2low\Factory\PDOFactory;
use S2low\Kernel;
use S2low\Services\Database\TransactionForPDO;
use S2low\Tests\DatabaseForIntegrationTests;
use S2lowLegacy\Class\Authentification;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Class\HttpsConnexion;
use S2lowLegacy\Class\PasswordHandler;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\PemCertificate;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2lowLegacy\Lib\SessionWrapper;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\NounceSQL;
use S2lowLegacy\Model\UserSQL;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class S2lowIntegrationTestCase extends WebTestCase
{
    /**
     * @description Pour ajouter de nouveaux utilisateurs : ajouter dans s2low-test.sql un nouvel user avec certificat puis l'ajouter ci-dessous
     * avant de pouvoir se logger avec $this->logAs($userId).
     */
    private array $usersCertificates = [
        //user_id => certificat path
        101 => __DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem',
        102 => __DIR__ . '/../test/api/robert_petitpoids_rgs.pem', // <= Non rgs certif
        103 => __DIR__ . '/../test/api/Charles DUTHEIL - User S2low.pem',
        113 => __DIR__ . '/fixtures/charles S2low - charles.dutheil@libriciel.coop.pem',
        150 => __DIR__ . '/../test/PHPUnit/controller/fixtures/user1.pem', // <= certif utilise par plusieurs users donc : login / password
    ];

    protected array $serverCertificatEnvVar;
    protected PemCertificateFactory $pemCertificateFactory;
    protected PemCertificate $fixtureCertificate;
    private TransactionForPDO $transactionForPdo;
    protected KernelBrowser $client;

    /**
     * @param int|string $dataName
     *
     * @internal This method is not covered by the backward compatibility promise for PHPUnit
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->pemCertificateFactory = new PemCertificateFactory();
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel('test', true);
    }

    public function getSQLQuery(): SQLQuery
    {
        return self::getContainer()->get(SQLQuery::class);
    }

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $this->logAs(113);

        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_SERVER['QUERY_STRING'] = '';

        $this->projectDir = self::getContainer()->getParameter("kernel.project_dir");
        vfsStream::setup('test');
        $this->tmpPathFolder = vfsStream::url('test');

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['QUERY_STRING'] = '';

        $this->transactionForPdo->rollbackTestTransaction();
        self::getContainer()->get(Database::class)->disconnect();
        self::getContainer()->get(PDOFactory::class)->closeAll();


        parent::tearDown();
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
        return $this->getAuthenticatedClient();
    }

    public function getAuthenticatedClient(): KernelBrowser
    {
        if (empty($this->fixtureCertificate)) {
            $this->logAs(113);
        }

        return $this->createClientWithCertificat(
            $this->fixtureCertificate->getContent(),
            $this->fixtureCertificate->getContentStrippedFromBegin()
        );
    }

    /**
     * @param string $certificatPem
     * @param string $certificatSansBegin
     * @return KernelBrowser
     */
    protected function createClientWithCertificat(string $certificatPem, string $certificatSansBegin, bool $clientVerifySuccess = true): KernelBrowser
    {
        $this->serverCertificatEnvVar = [
            'SSL_CLIENT_VERIFY' => $clientVerifySuccess ? 'SUCCESS' : null,
            'SSL_CLIENT_S_DN' => 'subject_dn',
            'SSL_CLIENT_I_DN' => 'issuer_dn',
            'SSL_CLIENT_CERT' => $certificatPem,
            'HTTP_ORG_S2LOW_FORWARD_X509_IDENTIFICATION' => $certificatSansBegin
        ];
        $this->addCertificatToServeurEnvironnement($this->serverCertificatEnvVar);

        self::ensureKernelShutdown();
        $client = static::createClient(
            [],
            $this->serverCertificatEnvVar
        );

        $sqlQuery = self::getContainer()->get(SQLQuery::class);
        $databaseForTest = new DatabaseForIntegrationTests($sqlQuery);
        self::getContainer()->set(Database::class, $databaseForTest);
        $this->transactionForPdo = self::getContainer()->get(TransactionForPDO::class);
        $this->transactionForPdo->beginTestTransaction();

        return $client;
    }

    private function addCertificatToServeurEnvironnement(array $serverVariables): void
    {
        foreach ($serverVariables as $key => $value) {
            $_SERVER[$key] = $value;
        }
    }

    protected function setUserAuthority(int $authorityId, int $userId = 113): void
    {
        self::getContainer()->get(Database::class)->query('UPDATE users SET authority_id = ? WHERE users.id = ?', [$authorityId, $userId]);
    }

    protected function setUserWithRole(UserRole $userRole, int $userId = 113): void
    {
        self::getContainer()->get(Database::class)->query('UPDATE users SET role = ? WHERE id = ?', [$userRole->value, $userId]);
    }

    protected function setUserWithPermission(ModulePermission $modulePermission): void
    {
        self::getContainer()->get(Database::class)->query('UPDATE users_perms SET perm = ? WHERE user_id = 113 AND module_id = 1', [$modulePermission->value]);
        self::getContainer()->get(Database::class)->query('UPDATE users_perms SET perm = ? WHERE user_id = 113 AND module_id = 2', [$modulePermission->value]);
        self::getContainer()->get(Database::class)->query('UPDATE users_perms SET perm = ? WHERE user_id = 113 AND module_id = 3', [$modulePermission->value]);
    }

    protected function logAs(int $userId): void
    {
        $file = file_get_contents(
            $this->usersCertificates[$userId]
        );

        $this->fixtureCertificate = $this->pemCertificateFactory->getFromString(
            $file
        );

        $this->client = $this->getAuthenticatedClient();
    }

    protected function logWithoutCertificat(): KernelBrowser
    {
        self::ensureKernelShutdown();
        return static::createClient([], $this->setServerAdullactCertificate());
    }

    protected function setAuthorityGroupUserAs(int $authorityGroupId, int $userId = 113): void
    {
        self::getContainer()->get(Database::class)->query('UPDATE users SET authority_group_id = ? WHERE id = ?', [$authorityGroupId, $userId]);
    }

    protected function getAuthentication(
        $server = [],
        $idLogin = null,
        $get = [],
        $certHandler = null,
        $convertLoginFromIso = false,
        $post = [],
        $environnement = null
    ): Authentification {
        $session = self::getContainer()->get(SessionWrapper::class);
        if ($idLogin !== null) {
            $session->set('id_login', $idLogin);
        }
        $environnement = $environnement ?? new Environnement(
            $get,
            $post,
            [],
            $session,
            $server,
            $convertLoginFromIso,
        );

        $httpsConnexion = new HttpsConnexion(
            $environnement,
            $certHandler ?? self::getContainer()->get(X509Certificate::class),
        );

        return new Authentification(
            $environnement,
            self::getContainer()->get(UserSQL::class),
            self::getContainer()->get(PasswordHandler::class),
            $httpsConnexion,
            self::getContainer()->get(NounceSQL::class),
        );
    }

    protected function setServerAdullactCertificate(): array
    {
        $server = $this->serverCertificatEnvVar;
        $server['SSL_CLIENT_VERIFY'] = "SUCCESS";
        $server['SSL_CLIENT_S_DN'] = "adullact";
        $server['SSL_CLIENT_I_DN'] = "adullact";
        $server['TESTING_CERTIFICATE_HASH'] = "hash_adullact";

        return $server;
    }
}
