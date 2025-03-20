<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\ObjectInstancierFactory;

class GetDocumentOfTransaction extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;

    private ?ActesTransactionsSQL $actesTransactionsSQL;
    private const TRANSMISSION_D_ACTE = 1;
    private const DEMANDE_PIECE_COMPLEMENTAIRES = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actesTransactionsSQL = new ActesTransactionsSQL($this->sqlQuery);
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return $this->actesTransactionsSQL;
    }

    protected function transactionTypeProvider()
    {
        return [
            [self::TRANSMISSION_D_ACTE, "('Content-type: text/plain','1','')", "actes_download_file.php"],
            [self::DEMANDE_PIECE_COMPLEMENTAIRES, "actes_download_file.php", "%String not int header%"],
        ];
    }

    /**
     * @dataProvider transactionTypeProvider
     */
    public function testShouldGetDocument($transactionType, $strInHeader, $strNotInHeader): void
    {
        $transactionId = $this->createTransactionOfType(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU, $transactionType);

        $_GET['id'] = $transactionId;

        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::Utilisateur);
        $client->request('GET', '/modules/actes/actes_transac_get_document.php', [
            'id' => $transactionId
        ]);

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertStringContainsString($strInHeader, $content);
        static::assertStringNotContainsString($strNotInHeader, $content);
    }
}
