<?php

declare(strict_types=1);

namespace IntegrationTests;

use S2lowLegacy\Class\LegacyObjectsManager;

/**
 *
 */
class HeliosIndexTestCase extends S2lowIntegrationTestCase
{
    /**
     * @throws \Exception
     */
    public function testEditAuthority(): void
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

        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $crawler = $client->request(
            'POST',
            '/modules/helios/index.php'
        );

        static::assertMatchesRegularExpression(
            '#Afficher par page#',
            $crawler->html()
        );
    }

    /**
     * @throws \Exception
     */
    public function testUserWithNoAccess(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUserWithNoPermissions($certificatePem->getContent(), $certificatePem->getHash());

        $client = $this->setUpClient(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );                                                           // 2/ Le client ne modifie pas la variable _SERVER

        LegacyObjectsManager::setLegacyObjectInstancier();

        $_SERVER['QUERY_STRING'] = '';  // Autrement, ça ne fonctionne pas ...
        $crawler = $client->request(
            'POST',
            '/modules/helios/index.php'
        );

        var_dump($crawler->html());
        static::assertMatchesRegularExpression(
            '#exit\(\) called with status 1#',
            $crawler->html()
        );
    }
}
