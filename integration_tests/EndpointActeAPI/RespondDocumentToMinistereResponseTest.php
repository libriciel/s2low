<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\ObjectInstancierFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RespondDocumentToMinistereResponseTest extends S2lowIntegrationTestCase
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

    protected function dataProvider(): array
    {
        return [
            [
                [
                    'typeEnvoi' => 3,
                    'typeActe' => '12345',
                    'typePj' => '12345',
                    'natureCode' => '3',
                    'stringInResponse' => 'OK',
                ]
            ],
            [
                [
                    'typeEnvoi' => 3,
                    'typeActe' => '1234',
                    'typePj' => '12345',
                    'natureCode' => '3',
                    'stringInResponse' => 'Le code de la PJ doit faire 5 carac',
                ]
            ],
            [
                [
                    'typeEnvoi' => 3,
                    'typeActe' => '',
                    'typePj' => '',
                    'natureCode' => '',
                    'stringInResponse' => 'typologie absente',
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testShouldAcceptDocument($data): void
    {
        $transactionId = $this->createTransactionOfType(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU, 2);

        $api = 1;
        $typeEnvoie = $data['typeEnvoi'];
        $typeActe = $data['typeActe'];
        $typePj = $data['typePj'];
        $natureCode = $data['natureCode'];

        $filePath = __DIR__ . '/../fixtures' . '/PDFTest.pdf';
        $fileName = 'PDFTest.pdf';
        $fileType = 'application/pdf';
        $fileError = UPLOAD_ERR_OK;

        $_POST['api'] = $api;
        $_POST['id'] = $transactionId;
        $_POST['type_envoie'] = $typeEnvoie;
        $_POST['type_acte'] = $typeActe;
        $_POST['type_pj'] = $typePj;
        $_POST['nature_code'] = $natureCode;

        $_FILES['acte_pdf_file'] = [
            'name' => $fileName,
            'type' => $fileType,
            'tmp_name' => $filePath,
            'error' => $fileError,
            'size' => null
        ];

        $uploadedFile = new UploadedFile(
            $filePath,
            $fileName,
            $fileType,
            $fileError,
            true
        );

        $client = $this->client;
        $this->setUserWithRole(UserRole::Utilisateur);

        $client->request(
            'POST',
            '/modules/actes/actes_transac_reponse_create.php',
            [
                'api' => $api,
                'id' => $transactionId,
                'type_envoie' => $typeEnvoie,
                'type_acte' => $typeActe,
                'type_pj' => $typePj,
                'nature_code' => $natureCode,
            ],
            [
                'acte_pdf_file' => $uploadedFile
            ]
        );

        $response = $client->getResponse();
        static::assertStringContainsString($data['stringInResponse'], $response->getContent());
    }

    public function testRefusesToRespondToDocumentOfAnotherAuthority(): void
    {
        $userOfAnotherAuthority = 51;
        $transactionId = $this->createTransactionOfType(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU, 2, '', '2017-07-01', '2017-07-01', $userOfAnotherAuthority);
        $filePath = __DIR__ . '/../fixtures/PDFTest.pdf';
        $postedData = ['api' => 1, 'id' => $transactionId, 'type_envoie' => 3, 'type_acte' => '12345', 'type_pj' => '12345', 'nature_code' => '3'];
        $_POST = $postedData;
        $_FILES['acte_pdf_file'] = ['name' => 'PDFTest.pdf', 'type' => 'application/pdf', 'tmp_name' => $filePath, 'error' => UPLOAD_ERR_OK, 'size' => null];
        $this->setUserWithRole(UserRole::Utilisateur);

        $this->client->request(
            'POST',
            '/modules/actes/actes_transac_reponse_create.php',
            $postedData,
            ['acte_pdf_file' => new UploadedFile($filePath, 'PDFTest.pdf', 'application/pdf', UPLOAD_ERR_OK, true)]
        );

        static::assertStringContainsString(
            "KO\n" . mb_convert_encoding('Accès refusé', 'ISO-8859-1', 'UTF-8'),
            $this->client->getResponse()->getContent()
        );
        static::assertSame(
            0,
            $this->sqlQuery->queryOne('SELECT count(*) FROM actes_transactions WHERE related_transaction_id = ?', $transactionId)
        );
    }
}
