<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\ObjectInstancierFactory;

class GetAllFilesOfAnActeTest extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;

    private ?ActesTransactionsSQL $actesTransactionsSQL;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actesTransactionsSQL = new ActesTransactionsSQL($this->sqlQuery);
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return $this->actesTransactionsSQL;
    }

    protected function transactionIdsProvider(): array
    {
        return [
            [true, 'PDFTest.pdf'],
            [false, 'Numéro de transaction invalide'],
        ];
    }

    /**
     * @dataProvider transactionIdsProvider
     */
    public function testShouldReturnOk($isRealTransaction, $stringInResponse): void
    {
        $this->setUserWithRole(UserRole::Utilisateur);
        if ($isRealTransaction) {
            $transactionId = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);
            $this->createActeIncludedFiles($transactionId);
        } else {
            $transactionId = 121414;
        }

        $_GET['transaction'] = $transactionId;

        $client = $this->client;
        $client->request(
            'GET',
            '/modules/actes/actes_transac_get_files_list.php',
            [
                'transaction' => $transactionId,

            ],
        );

        $response = $client->getResponse();

        static::assertStringContainsString($stringInResponse, $response->getContent());

        $files = json_decode($response->getContent(), true) ?? [];

        foreach ($files as $file) {
            static::assertArrayHasKey('id', $file);
            static::assertArrayHasKey('name', $file);
            static::assertArrayHasKey('posted_filename', $file);
            static::assertArrayHasKey('mimetype', $file);
            static::assertArrayHasKey('size', $file);
            static::assertArrayHasKey('sign', $file);
            static::assertArrayHasKey('code_pj', $file);
        }
    }
}
