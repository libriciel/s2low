<?php

declare(strict_types=1);

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;

class GetDocumentOfTransactionTest extends S2lowIntegrationTestCase
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

    protected function transactionTypeProvider(): iterable
    {
        return [
            [self::TRANSMISSION_D_ACTE, "('Content-type: text/plain','1','0')", 'actes_download_file.php'],
            [self::DEMANDE_PIECE_COMPLEMENTAIRES, 'actes_download_file.php', '%String not int header%'],
        ];
    }

    /**
     * @dataProvider transactionTypeProvider
     */
    public function testShouldGetDocument(int $transactionType, string $strInHeader, string $strNotInHeader): void
    {
        $transactionId = $this->createTransactionOfType(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU, $transactionType);

        $_GET['id'] = $transactionId;

        $client = $this->client;
        $this->setUserWithRole(UserRole::Utilisateur);
        $client->request('GET', '/modules/actes/actes_transac_get_document.php', [
            'id' => $transactionId
        ]);

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertStringContainsString($strInHeader, $content);
        static::assertStringNotContainsString($strNotInHeader, $content);
    }

    public function testRefusesDocumentsOfTransactionOfAnotherAuthority(): void
    {
        $userOfAnotherAuthority = 51;
        $transactionId = $this->createTransactionOfType(
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
            self::TRANSMISSION_D_ACTE,
            '',
            '2017-07-01',
            '2017-07-01',
            $userOfAnotherAuthority
        );
        $courrierId = $this->createTransactionOfType(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU, self::DEMANDE_PIECE_COMPLEMENTAIRES);
        $this->sqlQuery->query('UPDATE actes_transactions SET related_transaction_id = ? WHERE id = ?', $transactionId, $courrierId);
        $this->setUserWithRole(UserRole::Utilisateur);
        $_GET['id'] = $transactionId;

        $this->client->request('GET', '/modules/actes/actes_transac_get_document.php', ['id' => $transactionId]);

        $content = $this->client->getResponse()->getContent();
        static::assertStringContainsString("KO\nAccès refusé", $content);
        static::assertStringNotContainsString("-$courrierId\n", $content);
    }
}
