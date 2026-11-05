<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
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

    public function testImportDoesNotRunCommandFromArchiveMember(): void
    {
        $this->setUserWithRole(UserRole::Utilisateur);

        $marker = sys_get_temp_dir() . '/s2low_audit037_' . bin2hex(random_bytes(6));
        @unlink($marker);

        // Rebuild the valid fixture with a unique internal number and three extra
        // files that turn "tar cf - *" into command execution.
        $build_dir = sys_get_temp_dir() . '/audit037_build_' . bin2hex(random_bytes(6));
        mkdir($build_dir);
        (new \Symfony\Component\Process\Process(
            ['tar', 'xzf', __DIR__ . '/../fixtures/abc-TACT--123456789--20250313-0.tar.gz', '-C', $build_dir]
        ))->mustRun();
        $acteXml = $build_dir . '/034-123456789-20250306-43636243-AI-1-1_0.xml';
        $unique = '37' . date('ymdHis');
        file_put_contents(
            $acteXml,
            preg_replace('/NumeroInterne="[^"]*"/', 'NumeroInterne="' . $unique . '"', file_get_contents($acteXml)),
        );
        file_put_contents($build_dir . '/pwn.sh', 'touch ' . escapeshellarg($marker) . "\n");
        touch($build_dir . '/--checkpoint=1');
        touch($build_dir . '/--checkpoint-action=exec=sh pwn.sh');

        $toTestFileName = 'abc-TACT--123456789--20250313-1.tar.gz';
        $toTestFilePath = sys_get_temp_dir() . '/' . $toTestFileName;
        $members = array_values(array_diff(scandir($build_dir), ['.', '..']));
        (new \Symfony\Component\Process\Process(
            array_merge(['tar', 'czf', $toTestFilePath, '-C', $build_dir, '--'], $members)
        ))->mustRun();

        $_FILES['enveloppe'] = [
            'name' => $toTestFileName,
            'type' => 'application/gzip',
            'tmp_name' => $toTestFilePath,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($toTestFilePath),
        ];
        $_POST['api'] = 1;

        try {
            $this->client->request(
                'POST',
                '/modules/actes/actes_transac_submit.php',
                ['api' => 1],
                [
                    'enveloppe' => [
                        new UploadedFile(
                            $toTestFilePath,
                            $toTestFileName,
                            'application/gzip',
                            UPLOAD_ERR_OK,
                            true,
                        ),
                    ],
                ],
            );

            static::assertFileDoesNotExist($marker, 'The archive member name was executed as a command');
        } finally {
            @unlink($marker);
            @unlink($toTestFilePath);
            (new Filesystem())->remove($build_dir);
        }
    }
}
