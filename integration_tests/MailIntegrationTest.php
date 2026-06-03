<?php

declare(strict_types=1);

namespace IntegrationTests;

use S2low\Enum\UserRole;
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
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $crawler = $client->request('GET', '/');
        static::assertMatchesRegularExpression(
            '#<title>Tiers de télétransmission multiprotocoles</title>#',
            $crawler->html()
        );
        static::assertResponseIsSuccessful();
    }

    /**
     * @throws \Exception
     */
    public function testAdminUtilitiesControllerdoSendWithRightCertificateButNoData()
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);
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
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);
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
