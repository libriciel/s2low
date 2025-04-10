<?php

namespace IntegrationTests\EndpointApiHelios;

use IntegrationTests\S2lowIntegrationTestCase;
use HeliosUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\helios\IWorkspace;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class DownloadAcquitFileTest extends S2lowIntegrationTestCase
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
                    'status' => HeliosTransactionsSQL::ACQUITTE,
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDownloadAcquitFile($data): void
    {
        $this->createUserWithDefaultCertificatAs(UserRole::Utilisateur);

        $transactionId = $this->createTransaction(
            1,
            $data['status'],
        );

        $_GET['id'] = $transactionId;

        $client = $this->getAuthenticatedClientAttachedToDefaultCertificat();

        $sampleXMLPath = __DIR__ . "/../../integration_tests/fixtures/XMLTest.xml";
        $acquitFilename = "XMLTest.xml";
        $newSampleXML = LegacyObjectsManager::getLegacyObjectInstancier()
                ->get(IWorkspace::class)->getHeliosResponsesRoot() . '/' . $acquitFilename;
        copy($sampleXMLPath, $newSampleXML);

        $this->addPESAcquitTo($transactionId, $acquitFilename);

        $client->request(
            'GET',
            '/modules/helios/helios_download_acquit.php',
            [
                'id' => $transactionId,
            ],
        );

        $response = $client->getResponse();
        static::assertStringContainsString('Content-type: text/xml', $response->getContent());
        static::assertStringContainsString('<element>Contenu</element>', $response->getContent());
        unlink($newSampleXML);
    }
}
