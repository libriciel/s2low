<?php

namespace IntegrationTests\EndpointActeAPI;

use IntegrationTests\S2lowIntegrationTestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\ObjectInstancierFactory;

class DownloadFileTest extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;

    private ?ActesTransactionsSQL $actesTransactionsSQL;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actesTransactionsSQL = new ActesTransactionsSQL($this->sqlQuery);
        $this->setUserWithRole(UserRole::Utilisateur);
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return $this->actesTransactionsSQL;
    }

    protected function getObjectInstancier(): ObjectInstancier
    {
        return ObjectInstancierFactory::getObjetInstancier();
    }

    public function testShouldReturnFile(): void
    {
        $files = $this->getFiles();

        $_GET['file'] = $files[0]['id'];
        $_GET['tampon'] = false;
        $_GET['date_affichage'] = date('Y-m-d');

        $this->client->request(
            'GET',
            '/modules/actes/actes_download_file.php',
            [
                'file' => $_GET['file'],
                'tampon' => $_GET['tampon'],
                'date_affichage' => $_GET['date_affichage'],
            ],
        );

        $response = $this->client->getResponse();
        $content = $response->getContent();

        static::assertStringContainsString("Content-type: application/pdf", $content);
        static::assertStringContainsString("filename=\"PDFTest.pdf\"", $content);
    }

    private function getFiles()
    {
        $archiveName = 'abc-TACT--123456789--20250313-0.tar.gz';
        $archivePath = __DIR__ . '/../fixtures/' . $archiveName;

        $vfsUrl = vfsStream::url('test/' . $archiveName);
        copy($archivePath, $vfsUrl);
        $archivePathFromVfs = $vfsUrl;

        $transactionId = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU, $archivePathFromVfs);

        $this->createActeIncludedFiles($transactionId);

        $_GET['transaction'] = $transactionId;

        $this->client->request(
            'GET',
            '/modules/actes/actes_transac_get_files_list.php',
            [
                'transaction' => $transactionId,

            ],
        );

        $response = $this->client->getResponse();
        $content = explode("\n", trim($response->getContent()));

        static::assertNotSame('KO', $content[0]);
        static::assertJson($content[0]);

        return json_decode($response->getContent(), true);
    }
}
