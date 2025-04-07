<?php

namespace EndpointApiHelios;

use HeliosUtilitiesTestTrait;
use IntegrationTests\S2lowIntegrationTestCase;
use org\bovigo\vfs\vfsStream;
use S2low\Enum\UserRole;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CreatePESAllerTest extends S2lowIntegrationTestCase
{
    use HeliosUtilitiesTestTrait;

    private HeliosTransactionsSQL $heliosTransactionsSQL;

    public function setUp(): void
    {
        parent::setUp();
        $this->heliosTransactionsSQL = new HeliosTransactionsSQL($this->getSQLQuery());
    }

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return $this->heliosTransactionsSQL;
    }

    protected function dataProvider(): array
    {
        return [
            [
                [
                    'status' => HeliosTransactionsSQL::ACQUITTE,
                    'with_enveloppe' => true,
                    'resultat' => 'OK',
                    'message' => 'Téléchargement du fichier réussi.',
                ]
            ],
            [
                [
                    'status' => HeliosTransactionsSQL::ACQUITTE,
                    'with_enveloppe' => false,
                    'resultat' => 'KO',
                    'message' => 'Aucune enveloppe trouvée : la taille de l\'enveloppe dépasse probablement la taille maximum',
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testCreatePESAller($data): void
    {
        $this->createUserWithDefaultCertificatAs(UserRole::Utilisateur);

        $this->createTransaction(
            1,
            $data['status'],
        );

        $filePathFromFixtures = __DIR__ . "/../../integration_tests/fixtures/XMLTest.xml";
        $fileName = 'XMLTest.xml';

        $filePathFromVfs = vfsStream::url('test/helios/' . $fileName);
        copy($filePathFromFixtures, $filePathFromVfs);

        $fileContent = file_get_contents($filePathFromFixtures);
        $fileType = 'application/xml';
        $fileError = UPLOAD_ERR_OK;

        $toUploadFile = new UploadedFile(
            $filePathFromVfs,
            "",
            $fileContent,
            UPLOAD_ERR_OK,
            true
        );

        $_FILES['enveloppe'] = '';
        if ($data['with_enveloppe']) {
            $_FILES['enveloppe'] = [
                'name' => $fileName,
                'type' => $fileType,
                'tmp_name' => $filePathFromVfs,
                'error' => $fileError,
                'size' => 107
            ];
        }

        $client = $this->getAuthenticatedClientAttachedToDefaultCertificat();
        $client->request(
            'GET',
            '/modules/helios/api/helios_importer_fichier.php',
            [],
            [
                'enveloppe' => $toUploadFile,
            ],
        );

        $response = $client->getResponse();
        static::assertMatchesRegularExpression('/<import>(.*?)<\/import>/s', $response->getContent());

        if ($data['resultat'] === 'OK') {
            static::assertMatchesRegularExpression('/<id>(\d+)<\/id>/s', $response->getContent());
            static::assertMatchesRegularExpression(
                '/<resultat>' . $data['resultat'] . '<\/resultat>/s',
                $response->getContent()
            );
        }

        static::assertMatchesRegularExpression(
            '/<message>' . $data['message'] . '<\/message>/s',
            $response->getContent()
        );
    }
}
