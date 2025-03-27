<?php

namespace EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CreateActeFromArchive extends S2lowIntegrationTestCase
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

        $filePath = __DIR__ . '/../fixtures/abc-TACT--123456789--20250313-1.tar.gz';
        $fileName = 'abc-TACT--123456789--20250313-1.tar.gz';
        $fileType = 'application/gzip';
        $fileError = UPLOAD_ERR_OK;
        $fileSize = fileSize($filePath);

        $api = 1;

        $file = [];
        if ($data['with_file']) {
            $_FILES['enveloppe'] = [
                'name' => $fileName,
                'type' => $fileType,
                'tmp_name' => $filePath,
                'error' => $fileError,
                'size' => $fileSize,
            ];

            $file = [
                'enveloppe' => [
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


        $client->request(
            'POST',
            '/modules/actes/actes_transac_submit.php',
            [
                'api' => $api,
            ],
            $file
        );

        $response = $client->getResponse();
        var_dump($response->getContent());
        static::assertStringContainsString($data['stringInResponse'], $response->getContent());
    }
}
