<?php

namespace IntegrationTests;

use Exception;
use org\bovigo\vfs\vfsStream;
use S2low\Enum\ModulePermission;
use S2low\Enum\UserRole;
use S2low\Factory\PDOFactory;
use S2low\Kernel;
use S2low\Security\LegacyAuthenticationBridge;
use S2low\Security\SecurityUser;
use S2low\Security\SecurityUserProvider;
use S2lowLegacy\Class\Authentification;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\PemCertificate;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2lowLegacy\Lib\SQLQuery;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\ObjectInstancierFactory;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\DatabasePool;

class S2lowIntegrationTestCase extends WebTestCase
{
    /**
     * @description Pour ajouter de nouveaux utilisateurs : ajouter dans s2low-test.sql un nouvel user avec certificat puis l'ajouter ci-dessous
     * avant de pouvoir se logger avec $this->logAs($userId).
     */
    private array $usersCertificates = [
        //user_id => certificat path
        1 => __DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem',
        2 => __DIR__ . '/../test/api/robert_petitpoids_rgs.pem', // <= Non rgs certif
        3 => __DIR__ . '/../test/api/Charles DUTHEIL - User S2low.pem',
        13 => __DIR__ . '/fixtures/charles S2low - charles.dutheil@libriciel.coop.pem',
        50 => __DIR__ . '/../test/PHPUnit/controller/fixtures/user1.pem', // <= certif utilise par plusieurs users donc : login / password
    ];

    protected array $serverCertificatEnvVar;
    protected PemCertificateFactory $pemCertificateFactory;
    protected PemCertificate $fixtureCertificate;
    protected KernelBrowser $client;
    protected SQLQuery $sqlQuery;
    protected string $tmpPathFolder;
    protected string $projectDir;

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
        return $this->sqlQuery;
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
        $_FILES = [];
        $_SERVER['QUERY_STRING'] = '';

        // Authentifier l'utilisateur - cela crée le client et le container
        $this->logAs(13);

        // IMPORTANT: Initialiser la base de données APRÈS logAs()
        // mais la BDD doit être prête pour que logAs fonctionne
        // On initialise donc la BDD dans logAs() lui-même
        $this->sqlQuery = self::getContainer()->get(SQLQuery::class);

        $this->projectDir = self::getContainer()->getParameter("kernel.project_dir");
        vfsStream::setup('test');
        $this->tmpPathFolder = vfsStream::url('test');
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['QUERY_STRING'] = '';

        if (self::getContainer()) {
            try {
                $connection = self::getContainer()->get(\Doctrine\DBAL\Connection::class);
                if ($connection) {
                    if ($connection->getTransactionNestingLevel() === 0) {
                        $sql = file_get_contents($this->projectDir . '/test/PHPUnit/s2low-test.sql');
                        $connection->executeStatement($sql);
                        self::getContainer()->get(Database::class)->disconnect();
                        self::getContainer()->get(PDOFactory::class)->closeAll();
                    }
                }
            } catch (\Throwable) {
                // Ignore container/DB errors during shutdown
            }
        }

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
            $this->logAs(13);
        }

        return $this->createClientWithCertificat(
            $this->fixtureCertificate->getContent(),
        );
    }

    /**
     * @param string $certificatPem
     * @param bool $clientVerifySuccess
     * @return KernelBrowser
     */
    protected function createClientWithCertificat(string $certificatPem, bool $clientVerifySuccess = true): KernelBrowser
    {
        $this->serverCertificatEnvVar = [
            'SSL_CLIENT_VERIFY' => $clientVerifySuccess ? 'SUCCESS' : null,
            'SSL_CLIENT_S_DN' => 'subject_dn',
            'SSL_CLIENT_I_DN' => 'issuer_dn',
            'SSL_CLIENT_CERT' => $certificatPem,
        ];
        $this->addCertificatToServeurEnvironnement($this->serverCertificatEnvVar);

        self::ensureKernelShutdown();
        $client = static::createClient(
            [],
            $this->serverCertificatEnvVar
        );

        // Mettre à jour les factories legacy pour utiliser le container partagé
        // afin que les surcharges réalisées via self::getContainer()->set()
        // soient visibles depuis l'ObjectInstancier utilisé par le code legacy.
        $sharedContainer = self::getContainer();
        $objectInstancier = new ObjectInstancier($sharedContainer);
        LegacyObjectsManager::setObjectInstancier($objectInstancier);
        ObjectInstancierFactory::setObjectInstancier($objectInstancier);
        DatabasePool::setObjectInstancier($objectInstancier);

        return $client;
    }

    private function addCertificatToServeurEnvironnement(array $serverVariables): void
    {
        foreach ($serverVariables as $key => $value) {
            $_SERVER[$key] = $value;
        }
    }

    protected function setUserAuthority(int $authorityId, int $userId = 13): void
    {
        self::getContainer()->get(SQLQuery::class)->query('UPDATE users SET authority_id = ? WHERE users.id = ?', [$authorityId, $userId]);
    }

    protected function setUserWithRole(UserRole $userRole, int $userId = 13): void
    {
        self::getContainer()->get(SQLQuery::class)->query('UPDATE users SET role = ? WHERE id = ?', [$userRole->value, $userId]);
    }

    protected function setUserWithPermission(ModulePermission $modulePermission): void
    {
        self::getContainer()->get(SQLQuery::class)->query('UPDATE users_perms SET perm = ? WHERE user_id = 13 AND module_id = 1', [$modulePermission->value]);
        self::getContainer()->get(SQLQuery::class)->query('UPDATE users_perms SET perm = ? WHERE user_id = 13 AND module_id = 2', [$modulePermission->value]);
        self::getContainer()->get(SQLQuery::class)->query('UPDATE users_perms SET perm = ? WHERE user_id = 13 AND module_id = 3', [$modulePermission->value]);
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

        // IMPORTANT: Initialiser la BDD APRÈS création du client mais AVANT l'authentification
        // car authenticateUserInSecurityContext charge l'utilisateur depuis la BDD
        $pdo = self::getContainer()->get(PDOFactory::class)->create();
        $pdo->exec(file_get_contents(__DIR__ . '/../test/PHPUnit/s2low-test.sql'));

        // Authentifier l'utilisateur dans le contexte Symfony Security
        // pour les tests qui n'utilisent pas le client HTTP
        // Note: doit être appelé après getAuthenticatedClient() car celui-ci crée un nouveau kernel
        $this->authenticateUserInSecurityContext($userId);
    }

    /**
     * Authentifie un utilisateur directement dans le token storage de Symfony.
     * Utile pour les tests qui créent des contrôleurs directement sans passer par HTTP.
     *
     * IMPORTANT: Authentifie dans les deux containers (client et statique) car :
     * - Le container du client est utilisé pour les requêtes HTTP
     * - Le container statique (self::getContainer()) est utilisé par les tests
     *   qui appellent directement les méthodes des controllers
     */
    protected function authenticateUserInSecurityContext(int $userId): void
    {
        // Charger l'utilisateur depuis le container du client
        $clientContainer = $this->client->getContainer();
        $userProvider = $clientContainer->get(SecurityUserProvider::class);
        $user = $userProvider->loadUserByIdentifier((string) $userId);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        // Authentifier dans le container du client
        $clientContainer->get('security.token_storage')->setToken($token);

        // Authentifier également dans le container statique
        // pour les tests qui utilisent self::getContainer()
        self::getContainer()->get('security.token_storage')->setToken($token);
    }

    protected function logWithoutCertificat(): KernelBrowser
    {
        self::ensureKernelShutdown();
        return static::createClient([], $this->setServerAdullactCertificate());
    }

    protected function setAuthorityGroupUserAs(int $authorityGroupId, int $userId = 13): void
    {
        self::getContainer()->get(SQLQuery::class)->query('UPDATE users SET authority_group_id = ? WHERE id = ?', [$authorityGroupId, $userId]);
    }

    protected function getAuthentication(
        ?LegacyAuthenticationBridge $authBridge = null
    ): Authentification {
        $environnement = self::getContainer()->get(Environnement::class);

        // Si aucun bridge fourni, utiliser celui du container
        if ($authBridge === null) {
            $authBridge = self::getContainer()->get(LegacyAuthenticationBridge::class);
        }

        return new Authentification(
            $environnement,
            $authBridge
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
