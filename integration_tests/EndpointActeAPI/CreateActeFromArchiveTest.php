<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CreateActeFromArchiveTest extends S2lowIntegrationTestCase
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
                    'stringInResponse' => 'Importation fichier archive réussie.'
                ]
            ],
        ];
    }

    /**
     * @dataProvider changeStatusProvider
     */
    public function testShouldReturnOk($data): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::Utilisateur);

        $realFilePath = __DIR__ . '/../fixtures/abc-TACT--123456789--20250313-0.tar.gz';
        $filePathToTest = __DIR__ . '/../fixtures/abc-TACT--123456789--20250313-1.tar.gz';
        copy($realFilePath, $filePathToTest);

        $fileName = 'abc-TACT--123456789--20250313-1.tar.gz';

        $fileType = 'application/gzip';
        $fileError = UPLOAD_ERR_OK;
        $fileSize = fileSize($filePathToTest);

        $api = 1;

        $file = [];
        if ($data['with_file']) {
            $_FILES['enveloppe'] = [
                'name' => $fileName,
                'type' => $fileType,
                'tmp_name' => $filePathToTest,
                'error' => $fileError,
                'size' => $fileSize,
            ];

            $file = [
                'enveloppe' => [
                    new UploadedFile(
                        $filePathToTest,
                        $fileName,
                        $fileType,
                        $fileError,
                        true
                    )
                ]
            ];
        }

        $_POST['api'] = $api;


        $client->request(
            'POST',
            '/modules/actes/actes_transac_submit.php',
            [
                'api' => $api,
            ],
            $file
        );

        $response = $client->getResponse();
        static::assertStringContainsString($data['stringInResponse'], $response->getContent());
//        delete($filePathToTest);
    }
}
