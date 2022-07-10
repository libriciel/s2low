<?php


use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MailIntegrationTest extends WebTestCase
{
    private ObjectInstancier $objectInstancier;
    /** @var \SQLQuery */
    private $sqlQuery;
    private PemCertificateFactory $pemCertificateFactory;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->objectInstancier = ObjectInstancierFactory::getObjetInstancier();
        $this->sqlQuery = $this->objectInstancier->get(SQLQuery::class);
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
        $client = static::createClient(
            array(),
            $serverVariables
        );
        /** @var \Environnement $environment */
        //$environment = $this->objectInstancier->get(Environnement::class);
        //$environment->session()->set('id_login', null);              // L'environnement n'est pas RàZ entre deux tests !
        //foreach ($serverVariables as $key => $serverVariable) {     //Solution sale à deux problèmes :
        //    $environment->server()->set($key, $serverVariable);     // 1/ L'object Instancier est setté *avant* les tests ...
        //}                                                           // 2/ Le client ne modifie pas la variable _SERVER
        return $client;
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
