<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\TmpFolder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CreateActeFromArchiveTest extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;

    private ?ActesTransactionsSQL $actesTransactionsSQL;
    private Filesystem $filesystem;


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
        $client = $this->client;
        $this->setUserWithRole(UserRole::Utilisateur);

        $originalFileName = 'abc-TACT--123456789--20250313-0.tar.gz';
        $toTestFileName = 'abc-TACT--123456789--20250313-1.tar.gz';
        $originalFilePath = __DIR__ . '/../fixtures/' . $originalFileName;
        $toTestFilePath = sys_get_temp_dir() . '/' . $toTestFileName;

        try {
            copy($originalFilePath, $toTestFilePath);
        } catch (\Exception $e) {
            $this->fail("Impossible de copier le fichier de test : $originalFilePath");
        }

        $fileType = 'application/gzip';
        $fileError = UPLOAD_ERR_OK;
        $fileSize = fileSize($toTestFilePath);

        $api = 1;

        $file = [];
        if ($data['with_file']) {
            $_FILES['enveloppe'] = [
                'name' => $toTestFileName,
                'type' => $fileType,
                'tmp_name' => $toTestFilePath,
                'error' => $fileError,
                'size' => $fileSize,
            ];

            $file = [
                'enveloppe' => [
                    new UploadedFile(
                        $toTestFilePath,
                        $toTestFileName,
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

        try {
            static::assertStringContainsString($data['stringInResponse'], $response->getContent());
        } finally {
            @unlink($toTestFilePath);
        }
    }
}
