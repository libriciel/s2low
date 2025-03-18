<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\ObjectInstancierFactory;

class GetUnreadPrefectureDocumentsTest extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;

    private ?ActesTransactionsSQL $actesTransactionsSQL;
    private const DEMANDE_PIECES_COMPLEMENTAIRES = 3;

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

    public function testShouldListUnreadPrefectureDocuments(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::Utilisateur);
        $this->createTransactionOfType(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU, self::DEMANDE_PIECES_COMPLEMENTAIRES);
        $this->createTransactionOfType(ActesStatusSQL::STATUS_VALIDE, self::DEMANDE_PIECES_COMPLEMENTAIRES);

        $client->request('GET', '/modules/actes/api/list_document_prefecture.php');

        $response = $client->getResponse();
        $contentArray = json_decode($response->getContent(), true);
        var_dump($response->getContent());
        foreach ($contentArray as $document) {
            static::assertArrayHasKey('id', $document);
            static::assertArrayHasKey('type', $document);
            static::assertArrayHasKey('number', $document);
            static::assertArrayHasKey('unique_id', $document);
            static::assertArrayHasKey('last_status_id', $document);
            static::assertArrayHasKey('related_transaction_id', $document);
        }
    }
}
