<?php

use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2lowLegacy\Lib\SQLQuery;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MailIntegrationTest extends WebTestCase
{
    /** @var SQLQuery */
    private SQLQuery $sqlQuery;
    private PemCertificateFactory $pemCertificateFactory;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function setUp(): void
    {
        parent::setUp();
        \S2lowLegacy\Class\LegacyObjectsManager::resetObjectInstancier();
        $_SESSION = [];
        \S2lowLegacy\Lib\ObjectInstancierFactory::setObjectInstancier(new ObjectInstancier());    //DatabasePool utilise ObjectInstancier
        $this->sqlQuery = new SQLQuery(DB_DATABASE_TEST);            // On en crée un le temps de MàJ la BDD
        $this->sqlQuery->setCredential(DB_USER_TEST, DB_PASSWORD_TEST); // On le ressettera ensuite
        $this->sqlQuery->setDatabaseHost(DB_HOST_TEST);
        \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier()->set(SQLQuery::class, $this->sqlQuery);
        $this->pemCertificateFactory = new PemCertificateFactory();
        $this->sqlQuery->exec(utf8_encode(file_get_contents(__DIR__ . "/fixtures/s2low-test-init.sql")));
    }

    /**
     * @throws \Exception
     */
    public function setUpUser(string $certificatPem, string $certificatHash)
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
        $serverVariables = array(
            'SSL_CLIENT_VERIFY' => 'ssl_client_verify',
            'SSL_CLIENT_S_DN' => 'subject_dn',
            'SSL_CLIENT_I_DN' => 'issuer_dn',
            'SSL_CLIENT_CERT' => $certificatPem,
            'HTTP_ORG_S2LOW_FORWARD_X509_IDENTIFICATION' => $certificatSansBegin

        );
        foreach ($serverVariables as $key => $value) {
            $_SERVER[$key] = $value;         // Le client Symfony ne set pas la session, utilisée par l'appli...
        }
        return static::createClient(
            array(),
            $serverVariables
        );
    }

    /**
     * @throws \Exception
     */
    public function testAccessIndexWithRightCertificate()
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . "/../test/api/Eric_Pommateau_RGS_2_etoiles.pem")
        );

        $this->setUpUser($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpClient($certificatePem->getContent(), $certificatePem->getContentStrippedFromBegin());                                                           // 2/ Le client ne modifie pas la variable _SERVER

        \S2lowLegacy\Lib\ObjectInstancierFactory::resetObjectInstancier();
        $crawler = $client->request('GET', '/index.php');
        $this->assertMatchesRegularExpression(
            "#<title>Tiers de téléransmission multiprotocoles</title>#",
            $crawler->html()
        );
        $this->assertResponseIsSuccessful();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testAccessIndexWithWrongCertificate(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . "/../test/api/Eric_Pommateau_RGS_2_etoiles.pem")
        );
        $wrongCertificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . "/../test/PHPUnit/controller/fixtures/user1.pem")
        );

        $this->setUpUser($certificatePem->getContent(), $certificatePem->getHash());

        \S2lowLegacy\Lib\ObjectInstancierFactory::resetObjectInstancier();

        $client = $this->setUpClient(
            $wrongCertificatePem->getContent(),
            $wrongCertificatePem->getContentStrippedFromBegin()
        );
        $crawler = $client->request('GET', '/index.php');
        $this->assertMatchesRegularExpression(
            "#Le certificat n'est pas valide : aucun compte trouvé#",
            $crawler->html()
        );
    }
}
