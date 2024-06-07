<?php

declare(strict_types=1);

use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\ObjectInstancierFactory;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2lowLegacy\Lib\SQLQuery;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 *
 */
class AdminAuthorityEditHandlerTest extends WebTestCase
{
    /** @var SQLQuery */
    private SQLQuery $sqlQuery;
    private PemCertificateFactory $pemCertificateFactory;

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
    public function setUpUser(string $certificatPem, string $certificatHash): void
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
    private function setUpClient(string $certificatPem, string $certificatSansBegin): KernelBrowser
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

    /**
     * @throws \Exception
     */
    public function testEditAuthority(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUser($certificatePem->getContent(), $certificatePem->getHash());

        $client = $this->setUpClient(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );                                                           // 2/ Le client ne modifie pas la variable _SERVER

        LegacyObjectsManager::setLegacyObjectInstancier();

        $_POST = [
            'id' => '1',
            'name' => 'le nom',
            'siren' => '111',
            'authority_group_id' => 1,
            'agreement' => '',
            'email' => 'test@test.ts',
            'default_broadcast_email' => 'test@test.ts',
            'broadcast_email' => 'test@test.ts',
            'status' => 1,
            'authority_type_id' => 1,
            'address' => 'te',
            'postal_code' => '29620',
            'city' => 'SAN FRANCISCO',
            'department' => '001',
            'district' => '1',
            'telephone' => '0000000000',
            'fax' => '0000000000',
            'helios_ftp_dest' => 'test',
            'email_mail_securise' => 'fds@fds.r',
            'descr_mail_securise' => 'fds',
            'api' => 1
        ];
        $crawler = $client->request(
            'POST',
            '/admin/authorities/admin_authority_edit_handler.php'
        );

        static::assertMatchesRegularExpression(
            '#"status":"ok"#',
            $crawler->html()
        );

        $authority = new \S2lowLegacy\Class\Authority(1);
        $authority->init();

        $this->assertEquals('le nom', $authority->get('name'));
        $this->assertEquals('111', $authority->get('siren'));
            $this->assertEquals(1, $authority->get('authority_group_id'));
            $this->assertEquals('', $authority->get('agreement'));
            $this->assertEquals('test@test.ts', $authority->get('email'));
            $this->assertEquals('test@test.ts', $authority->get('default_broadcast_email'));
            $this->assertEquals('test@test.ts', $authority->get('broadcast_email'));
            $this->assertEquals(1, $authority->get('status'));
            $this->assertEquals(1, $authority->get('authority_type_id'));
            $this->assertEquals('te', $authority->get('address'));
            $this->assertEquals('29620               ', $authority->get('postal_code')); //WTF ??! Il y a des espaces en plus
            $this->assertEquals('SAN FRANCISCO', $authority->get('city'));
            $this->assertEquals('001', $authority->get('department'));
            $this->assertEquals('1', $authority->get('district'));
            $this->assertEquals('0000000000', $authority->get('telephone'));
            $this->assertEquals('0000000000', $authority->get('fax'));
            $this->assertEquals('test', $authority->get('helios_ftp_dest'));
            $this->assertEquals('fds@fds.r', $authority->get('email_mail_securise'));
        $this->assertEquals('fds', $authority->get('descr_mail_securise'));
    }

    /**
     * @throws \Exception
     */
    public function testCreateAuthority(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUser($certificatePem->getContent(), $certificatePem->getHash());

        $client = $this->setUpClient(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );                                                           // 2/ Le client ne modifie pas la variable _SERVER

        LegacyObjectsManager::setLegacyObjectInstancier();

        $_POST = [
            'name' => 'le nom',
            'siren' => '111',
            'authority_group_id' => 1,
            'agreement' => '',
            'email' => 'test@test.ts',
            'default_broadcast_email' => 'test@test.ts',
            'broadcast_email' => 'test@test.ts',
            'status' => 1,
            'authority_type_id' => 1,
            'address' => 'te',
            'postal_code' => '29620',
            'city' => 'SAN FRANCISCO',
            'department' => '001',
            'district' => '1',
            'telephone' => '0000000000',
            'fax' => '0000000000',
            'helios_ftp_dest' => 'test',
            'email_mail_securise' => 'fds@fds.r',
            'descr_mail_securise' => 'fds',
            'api' => 1
        ];
        $crawler = $client->request(
            'POST',
            '/admin/authorities/admin_authority_edit_handler.php'
        );

        static::assertMatchesRegularExpression(
            '#"status":"ok"#',
            $crawler->html()
        );

        $matches = [];
        preg_match('#{.*}#', $crawler->html(), $matches);
        $id = json_decode($matches[0])->id;

        $authority = new \S2lowLegacy\Class\Authority($id);
        $authority->init();

        $this->assertEquals('le nom', $authority->get('name'));
        $this->assertEquals('111', $authority->get('siren'));
        $this->assertEquals(1, $authority->get('authority_group_id'));
        $this->assertEquals('', $authority->get('agreement'));
        $this->assertEquals('test@test.ts', $authority->get('email'));
        $this->assertEquals('test@test.ts', $authority->get('default_broadcast_email'));
        $this->assertEquals('test@test.ts', $authority->get('broadcast_email'));
        $this->assertEquals(1, $authority->get('status'));
        $this->assertEquals(1, $authority->get('authority_type_id'));
        $this->assertEquals('te', $authority->get('address'));
        $this->assertEquals('29620               ', $authority->get('postal_code')); //WTF ??! Il y a des espaces en plus
        $this->assertEquals('SAN FRANCISCO', $authority->get('city'));
        $this->assertEquals('001', $authority->get('department'));
        $this->assertEquals('1', $authority->get('district'));
        $this->assertEquals('0000000000', $authority->get('telephone'));
        $this->assertEquals('0000000000', $authority->get('fax'));
        $this->assertEquals('test', $authority->get('helios_ftp_dest'));
        $this->assertEquals('fds@fds.r', $authority->get('email_mail_securise'));
        $this->assertEquals('fds', $authority->get('descr_mail_securise'));
    }
}
