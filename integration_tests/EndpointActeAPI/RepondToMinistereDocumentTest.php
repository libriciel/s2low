<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\ObjectInstancierFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RepondToMinistereDocumentTest extends S2lowIntegrationTestCase
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

    public function test(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::Utilisateur);

        $transactionId = $this->createTransactionOfType(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU, 2);

        $api = 1;
        $id = $transactionId;
        $typeEnvoie = 3;
        $typeActe = "12345";
        $typePj = "12345";
        $natureCode = "3";

        $filePath = __DIR__ . '/../fixtures' . '/PDFTest.pdf';
        $fileName = 'PDFTest.pdf';
        $fileType = 'application/pdf';
        $fileError = UPLOAD_ERR_OK;

        $_POST['api'] = $api;
        $_POST['id'] = $id;
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

        $client->request(
            'POST',
            '/modules/actes/actes_transac_reponse_create.php',
            [
                'api' => $api,
                'id' => $id,
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
        $content = explode("\n", trim($response->getContent()));

        static::assertSame('OK', $content[0]);
        static::assertNotEmpty($content[1]);
    }
}
