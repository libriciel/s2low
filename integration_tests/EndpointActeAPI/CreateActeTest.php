<?php

namespace EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CreateActeTest extends S2lowIntegrationTestCase
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

    public function changeStatusProvider(): array
    {
        return [
            [
                [
                    'with_file' => true,
                    'stringInResponse' => 'Création de l&#039;enveloppe'
                ]
            ],
            [
                [
                    'with_file' => false,
                    'stringInResponse' => 'Aucun fichier acte'
                ]
            ],
        ];
    }

    /**
     * @dataProvider changeStatusProvider
     */
    public function testShouldReturnOk($data): void
    {
        $this->client = $this->getAuthenticatedClient();
        $this->setUserWithRole(UserRole::Utilisateur);

        $filePath = __DIR__ . '/../fixtures/PDFTest.pdf';
        $fileName = 'PDFTest.pdf';
        $fileType = 'application/pdf';
        $fileError = UPLOAD_ERR_OK;
        $fileSize = fileSize($filePath);

        $api = 1;
        $natureCode = '1';
        $classif1 = 10;
        $classif2 = 20;
        $number = 'ACTE_20250002';
        $decisionDate = '2025-03-26';
        $subject = 'Test acte';
        $typeActe = 'PJ002';
        $typePj = 'PJ002';

        $file = [];
        if ($data['with_file']) {
            $_FILES['acte_pdf_file'] = [
                'name' => $fileName,
                'type' => $fileType,
                'tmp_name' => $filePath,
                'error' => $fileError,
                'size' => $fileSize,
            ];

            $file = [
                'acte_pdf_file' => [
                    new UploadedFile(
                        $filePath,
                        $fileName,
                        $fileType,
                        $fileError,
                        true
                    )
                ]
            ];
        }

        $_POST['api'] = $api;
        $_POST['nature_code'] = $natureCode;
        $_POST['classif1'] = $classif1;
        $_POST['classif2'] = $classif2;
        $_POST['number'] = $number;
        $_POST['decision_date'] = $decisionDate;
        $_POST['subject'] = $subject;
        $_POST['type_acte'] = $typeActe;
        $_POST['type_pj'] = $typePj;

        $this->client->request(
            'POST',
            '/modules/actes/actes_transac_create.php',
            [
                'api' => $api,
                'nature_code' => $natureCode,
                'classif1' => $classif1,
                'classif2' => $classif2,
                'number' => $number,
                'decision_date' => $decisionDate,
                'subject' => $subject,
                'type_acte' => $typeActe,
                'type_pj' => $typePj,
            ],
            $file
        );

        $response = $this->client->getResponse();
        static::assertStringContainsString($data['stringInResponse'], $response->getContent());
    }
}
