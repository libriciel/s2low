<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\ObjectInstancierFactory;

class GetTransactionBordereauAcquittementTest extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;

    private ?ActesTransactionsSQL $actesTransactionsSQL;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actesTransactionsSQL = self::getContainer()->get(ActesTransactionsSQL::class);
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return $this->actesTransactionsSQL;
    }

    public function testShouldReturnOk(): void
    {
        $transactionId = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        $_GET['trans_id'] = $transactionId;

        $client = $this->client;
        $this->setUserWithRole(UserRole::Utilisateur);

        $client->request('GET', '/modules/actes/actes_create_pdf.php', [
            'trans_id' => $transactionId
        ]);

        $response = $client->getResponse();

        static::assertStringContainsString(
            "%PDF-1.3
3 0 obj
<</Type /Page
/Parent 1 0 R
/Resources 2 0 R
/Contents 4 0 R>>",
            $response->getContent()
        );
    }
}
