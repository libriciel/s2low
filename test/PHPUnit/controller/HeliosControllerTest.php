<?php

use IntegrationTests\S2lowIntegrationTestCase;
use Psr\Log\LoggerInterface;
use S2low\Enum\UserRole;
use S2low\Services\LocalFileResolver;
use S2lowLegacy\Class\helios\PesAllerRetriever;
use S2lowLegacy\Class\RgsConnexion;
use S2lowLegacy\Controller\HeliosController;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Model\AuthoritySiretSQL;
use S2lowLegacy\Model\HeliosRetourSQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowLegacy\Model\UsersPermsSQL;

class HeliosControllerTest extends S2lowIntegrationTestCase
{
    private HeliosController $heliosController;
    private string $testHeliosPrefix;
    private HeliosTransactionsSQL $heliosTransactionSQL;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpPathFolder = '/tmp/tests';
        $this->testHeliosPrefix = $this->tmpPathFolder . "/helios";

        if (!is_dir($this->testHeliosPrefix)) {
            mkdir($this->testHeliosPrefix, recursive: true);
        }
        $tmp_file = $this->testHeliosPrefix . "/pes_aller.xml";
        $content = file_get_contents(__DIR__ . "/fixtures/pes_aller.xml");
        file_put_contents($tmp_file, $content);

        $_FILES['enveloppe'] = array(
            'name' => 'pes_aller.xml',
            'tmp_name' => $tmp_file,
            'size' => filesize($tmp_file),
            'error' => UPLOAD_ERR_OK
        );

        $pesAllerRetriever = new PesAllerRetriever(
            $this->tmpPathFolder,
            self::getContainer()->get(OpenStackSwiftWrapper::class),
            self::getContainer()->get(LoggerInterface::class),
        );

        self::getContainer()->set(PesAllerRetriever::class, $pesAllerRetriever);
        $this->heliosTransactionSQL = self::getContainer()->get(HeliosTransactionsSQL::class);
        $this->heliosController = $this->createHeliosController();
        $this->heliosController->setFiles($_FILES);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $files = [
            $this->testHeliosPrefix . '/' . 'pes_aller.xml',
            $this->testHeliosPrefix . '/' . 'pes1.xml',
            $this->testHeliosPrefix . '/' . 'empty_file.xml',
            $this->testHeliosPrefix . '/' . 'pes_aller_not_exist.xml',
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                $sha1 = sha1_file($file);
                if (file_exists($this->testHeliosPrefix . '/' . $sha1)) {
                    unlink($this->testHeliosPrefix . '/' . $sha1);
                }
                unlink($file);
            }
        }
    }

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testImportAction()
    {
        $this->expectException(Exception::class);
        $this->heliosController->importAction();
    }

    public function testImportAPIAction()
    {
        $this->setUserWithRole(UserRole::Utilisateur);
        $this->expectOutputRegex("#<resultat>OK</resultat>#");
        $this->importAPI();
        $output = $this->getActualOutput();

        $output = preg_replace("#header.*called\n#", "", $output);
        $xml = simplexml_load_string($output);
        $transaction_id = $xml->id;
        $info = $this->heliosTransactionSQL->getInfo($transaction_id);
        $this->assertEquals(HeliosTransactionsSQL::POSTE, $info['last_status_id']);
        $info_wf = $this->heliosTransactionSQL->getWorkflow($transaction_id);
        $this->assertEquals(1, $info_wf[0]['status_id']);
    }

    private function importAPI()
    {
        try {
            $this->heliosController->importAPIAction();
        } catch (Exception $e) {
        }
    }

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testImportMustPoster()
    {
        $userPermsSQL = self::getContainer()->get(UsersPermsSQL::class);
        $userPermsSQL->setPerms(2, 13, 'CS');
        $this->expectOutputRegex("#<resultat>OK</resultat>#");
        $this->importAPI();
        $output = $this->getActualOutput();
        $output = preg_replace("#header.*called\n#", "", $output);
        $xml = simplexml_load_string($output);
        $transaction_id = $xml->id;
        $info = $this->heliosTransactionSQL->getInfo($transaction_id);
        $this->assertEquals(HeliosTransactionsSQL::ATTENTE_POSTEE, $info['last_status_id']);
        $info_wf = $this->heliosTransactionSQL->getWorkflow($transaction_id);
        $this->assertEquals(HeliosTransactionsSQL::ATTENTE_POSTEE, $info_wf[0]['status_id']);
    }

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testImportApiError()
    {
        unset($_FILES);
        //$this->expectedError("Échec lors du téléchargement du fichier"); //BUG ??!! Le comportement semble normal
        $this->expectedError("Aucune enveloppe trouv\ée : la taille de l'enveloppe d\épasse probablement la taille maximum");
        $this->importAPI();
    }

    private function expectedError($message)
    {
        $message = htmlspecialchars($message, ENT_COMPAT, "UTF-8");
        $this->expectOutputRegex("#$message#");
    }

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testBadFile()
    {
        $tmp_file = $this->tmpPathFolder . "/pes_aller_not_exist.xml";
        $_FILES['enveloppe']['tmp_name'] = $tmp_file;
        $this->expectedError('Échec lors du téléchargement du fichier');
        $this->importAPI();
    }

    public function testEmptyFile()
    {
        $tmp_file = $this->tmpPathFolder . "/empty_file.xml";
        file_put_contents($tmp_file, file_get_contents(__DIR__ . "/fixtures/empty_file.xml"));

        $_FILES['enveloppe'] = array(
            'name' => 'empty_file.xml',
            'tmp_name' => $tmp_file,
            'size' => filesize($tmp_file),
            'error' => UPLOAD_ERR_OK
        );

        $this->expectedError("Le fichier pr\ésent\é est vide \(0 octet\)");
        $this->importAPI();
        $this->assertMatchesRegularExpression(
            "#Le fichier pr\ésent\é est vide \(0 octet\)#",
            $this->getActualOutput()
        );

        if (file_exists($tmp_file)) {
            unlink($tmp_file);
        }
    }

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testDuplicate()
    {
        $pesAller = $this->getLocalFileResolver()->getFullPathFromFilePath("pes_aller.xml");
        $this->expectOutputRegex("#<resultat>OK</resultat>#");
        $this->importAPI();
        file_put_contents($pesAller, file_get_contents(__DIR__ . "/fixtures/pes_aller.xml"));
        $this->expectOutputRegex("#doublon d\étect\é. Ce fichier a d\éj\à \ét\é post\é.\<#");
        $this->importAPI();
    }

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testMaxSize()
    {
        $this->heliosController->setHeliosMaxUploadSize(0);
        $this->expectedError("Taille de fichier supérieure à la limite autorisée");
        $this->importAPI();
    }

    public function testUpdateSiretFromPESAllerNoFile()
    {
        $this->heliosTransactionSQL->create("pes1.xml", "42", 13, 1, 42, 12);

        $this->expectOutputRegex("#le fichier PES ALLER n'est pas disponible#");
        $this->heliosController->updateSiretFromPESAller();
    }

    public function testUpdateSiretFromPESAllerNotXML()
    {
        $this->heliosTransactionSQL->create("pes1.xml", "d8d1a344f31de311d32134064695df85f3801897", 13, 1, 42, 12);
        file_put_contents($this->testHeliosPrefix . "/d8d1a344f31de311d32134064695df85f3801897", "<test/>");
        $this->expectOutputRegex("#le fichier PES ALLER ne contient pas de SIRET#");
        $this->heliosController->updateSiretFromPESAller();
    }

    public function testUpdateSiretFromPESAllerOK()
    {
        $this->heliosTransactionSQL->create("pes1.xml", "d8d1a344f31de311d32134064695df85f3801897", 13, 1, 42, 12);
        file_put_contents($this->testHeliosPrefix . "/d8d1a344f31de311d32134064695df85f3801897", file_get_contents(__DIR__ . "/fixtures/pes_aller.xml"));
        $this->expectOutputRegex("#siret 12345678912345 ajouté à la collectivite 1#");
        $this->heliosController->updateSiretFromPESAller();
        $authoritySiretSQL = self::getContainer()->get(AuthoritySiretSQL::class);
        ;
        $list = $authoritySiretSQL->siretList(1);
        $this->assertEquals("12345678912345", $list[0]['siret']);
    }

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testGetPESRetourEmptyListAction()
    {
        $this->expectOutputRegex("#<idColl>1</idColl>#");
        $this->expectExceptionMessage("exit() called");
        $this->heliosController->getPESRetourListAction();
    }

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testGetPESRetourListAction()
    {
        $heliosRetourSQL = self::getContainer()->get(HeliosRetourSQL::class);
        $heliosRetourSQL->add(1, "123456789", "toto.xml", 10, "sha1");

        $this->expectOutputRegex("#<nom>toto.xml</nom>#");
        $this->expectExceptionMessage("exit() called");
        $this->heliosController->getPESRetourListAction();
    }

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testGetPESRetourListActionForAdmin()
    {
        $this->setUserWithRole(UserRole::AdministrateurCollectivite);
        $heliosRetourSQL = self::getContainer()->get(HeliosRetourSQL::class);
        $heliosRetourSQL->add(1, "123456789", "toto.xml", 10, "sha1");

        $this->expectOutputRegex("#<nom>toto.xml</nom>#");
        $this->expectExceptionMessage("exit() called");
        $this->heliosController->getPESRetourListAction();
    }

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testGetPESRetourListActionForAdminUnauthorized()
    {
        $this->setUserWithRole(UserRole::AdministrateurCollectivite);
        $heliosRetourSQL = self::getContainer()->get(HeliosRetourSQL::class);
        $heliosRetourSQL->add(2, "123456789", "toto.xml", 10, "sha1");

        $this->expectOutputRegex("#^((?!toto.xml).)*$#s");
        $this->expectExceptionMessage("exit() called");
        $this->heliosController->getPESRetourListAction();
    }

    public function testGetPostPESRetourWithoutRGS()
    {
        $rgsConnexion = $this->getMockBuilder(RgsConnexion::class)->disableOriginalConstructor()->getMock();
        $rgsConnexion->method('isRgsConnexion')->willReturn(false);

        self::getContainer()->set(RgsConnexion::class, $rgsConnexion);

        $this->setUserWithRole(UserRole::Utilisateur);
        $this->expectOutputRegex("#<message>Votre certificat n'est pas RGS et ne vous permet donc pas de#");
        $this->expectExceptionMessage("exit() called");
        $this->heliosController->importAPIAction();
    }

    private function getLocalFileResolver(): LocalFileResolver
    {
        return new LocalFileResolver(
            self::getContainer()->get(HeliosTransactionsSQL::class),
            $this->testHeliosPrefix
        );
    }

    private function createHeliosController(): HeliosController
    {
        return new HeliosController(
            $this->getLocalFileResolver(),
            self::getContainer()->get('app.store.file.pes_aller'),
            self::getContainer()->get(\S2lowLegacy\Model\ModuleSQL::class),
            self::getContainer()->get(ObjectInstancier::class)
        );
    }
}
