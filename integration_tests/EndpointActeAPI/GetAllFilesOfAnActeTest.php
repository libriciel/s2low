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
        ObjectInstancierFactory::resetObjectInstancier();
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return $this->actesTransactionsSQL;
    }

    public function testShouldReturnOk(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::Utilisateur);

        $transactionId = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);
        $this->createActeIncludedFiles($transactionId);

        $_GET['transaction'] = $transactionId;

        $client->request(
            'GET',
            '/modules/actes/actes_transac_get_files_list.php',
            [
                'transaction' => $transactionId,

            ],
        );

        $response = $client->getResponse();
        $content = explode("\n", trim($response->getContent()));

        static::assertNotSame('KO', $content[0]);
        static::assertJson($content[0]);

        $files = json_decode($response->getContent(), true);

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
