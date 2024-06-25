<?php

declare(strict_types=1);

namespace IntegrationTests;

use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\ObjectInstancierFactory;

/**
 *
 */
class MailIntegrationTest extends S2lowIntegrationTestCase
{
    /**
     * @throws \Exception
     */
    public function testAccessIndexWithRightCertificate()
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->SuperAdmin($certificatePem->getContent(), $certificatePem->getHash());
        $client = $this->setUpClient(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );                                                           // 2/ Le client ne modifie pas la variable _SERVER

        ObjectInstancierFactory::resetObjectInstancier();
        $crawler = $client->request('GET', '/index.php');
        static::assertMatchesRegularExpression(
            '#<title>Tiers de téléransmission multiprotocoles</title>#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testAccessIndexWithWrongCertificate(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );
        $wrongCertificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/PHPUnit/controller/fixtures/user1.pem')
        );

        $this->SuperAdmin($certificatePem->getContent(), $certificatePem->getHash());

        ObjectInstancierFactory::resetObjectInstancier();

        $client = $this->setUpClient(
            $wrongCertificatePem->getContent(),
            $wrongCertificatePem->getContentStrippedFromBegin()
        );
        $crawler = $client->request('GET', '/index.php');
        static::assertMatchesRegularExpression(
            "#Le certificat n'est pas valide : aucun compte trouvé#",
            $crawler->html()
        );
    }

    /**
     * @throws \Exception
     */
    public function testAdminUtilitiesControllerdoSendWithRightCertificateButNoData()
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->SuperAdmin($certificatePem->getContent(), $certificatePem->getHash());

        $client = $this->setUpClient(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );                                                           // 2/ Le client ne modifie pas la variable _SERVER

        LegacyObjectsManager::setLegacyObjectInstancier();
        $crawler = $client->request('GET', '/admin/utilities/admin_send_global_message.php');

        static::assertMatchesRegularExpression(
            '#Redirecting to /admin/utilities/index.php#',
            $crawler->html()
        );
        static::assertResponseRedirects('/admin/utilities/index.php');
    }

    /**
     * @throws \Exception
     */
    public function testAdminUtilitiesControllerdoSendWithRightCertificateWithData()
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->SuperAdmin($certificatePem->getContent(), $certificatePem->getHash());

        $client = $this->setUpClient($certificatePem->getContent(), $certificatePem->getContentStrippedFromBegin());
        $postData = [ 'module' => '1', 'authority_group_id' => '1', 'subject' => 'le subject', 'body' => 'le body'];

        $client->request(
            'POST',
            '/admin/utilities/admin_send_global_message.php',
            $postData
        );

        static::assertMatchesRegularExpression(
            '#eric@sigmalis.com#',
            LegacyObjectsManager::getObject(Environnement::class)->session()->get('error')
        );
        static::assertResponseRedirects('/admin/utilities/index.php');
    }
}
