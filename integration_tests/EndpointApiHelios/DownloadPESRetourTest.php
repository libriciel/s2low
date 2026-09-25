<?php

namespace IntegrationTests\EndpointApiHelios;

use HeliosUtilitiesTestTrait;
use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class DownloadPESRetourTest extends S2lowIntegrationTestCase
{
    use HeliosUtilitiesTestTrait;

    private HeliosTransactionsSQL $heliosTransactionsSQL;

    public function setUp(): void
    {
        parent::setUp();
        $this->heliosTransactionsSQL = new HeliosTransactionsSQL($this->getSQLQuery());
    }

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return $this->heliosTransactionsSQL;
    }

    protected function dataProvider(): array
    {
        return [
            [
                [
                    'use_pes_retour_id' => true,
                    'use_good_pes_retour_id' => true,
                    'string_in_response' => '<element>Contenu</element>',
                ]
            ],
            [
                [
                    'use_pes_retour_id' => true,
                    'use_good_pes_retour_id' => false,
                    'string_in_response' => '<message>retour id n\'est pas correcte</message>',
                ]
            ],
            [
                [
                    'use_pes_retour_id' => false,
                    'use_good_pes_retour_id' => false,
                    'string_in_response' => '<message>retour id n\'est pas correcte</message>',
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDownloadPESRetour($data): void
    {
        $collectiviteId = 1;
        $sampleXMLPath = __DIR__ . "/../../integration_tests/fixtures/XMLTest.xml";
        $PESRetourFilename = "XMLTest.xml";
        $heliosResponseRoot = self::getContainer()->getParameter('app.helios_responses_root');

        $newSampleXML = $heliosResponseRoot . "/" . $PESRetourFilename;
        copy($sampleXMLPath, $newSampleXML);

        $PESRetourId = $this->addPESRetourToCollectivite($collectiviteId, $PESRetourFilename);

        $client = $this->client;
        $this->setUserWithRole(UserRole::Archiviste);

        if ($data['use_pes_retour_id']) {
            $PESRetourId = $data['use_good_pes_retour_id'] ? $PESRetourId : 1234567;
            $_GET['id'] = $PESRetourId;
            $requestGetParam = [
                'id' => $PESRetourId,
            ];
        } else {
            $requestGetParam = [];
        }
        $client->request(
            'GET',
            '/modules/helios/api/helios_get_retour.php',
            $requestGetParam,
        );

        unlink($newSampleXML);
        $response = $client->getResponse();
        static::assertStringContainsString($data['string_in_response'], $response->getContent());
    }

    public function testRefusesPESRetourOfAnotherAuthority(): void
    {
        $otherAuthorityId = 2;
        $PESRetourFilename = "XMLTest.xml";
        $PESRetourPath = self::getContainer()->getParameter('app.helios_responses_root') . "/" . $PESRetourFilename;
        copy(__DIR__ . "/../../integration_tests/fixtures/XMLTest.xml", $PESRetourPath);
        $PESRetourId = $this->addPESRetourToCollectivite($otherAuthorityId, $PESRetourFilename);
        $this->setUserWithRole(UserRole::Utilisateur);
        $_GET['id'] = $PESRetourId;

        $this->client->request('GET', '/modules/helios/api/helios_get_retour.php', ['id' => $PESRetourId]);

        unlink($PESRetourPath);
        $content = $this->client->getResponse()->getContent();
        static::assertStringContainsString('<message>Accès refusé</message>', html_entity_decode($content));
        static::assertStringNotContainsString('<element>Contenu</element>', $content);
    }
}
