<?php

use Monolog\Level;
use S2lowLegacy\Class\helios\HeliosAnalyseFichierRecu;
use S2lowLegacy\Model\AuthoritySiretSQL;
use S2lowLegacy\Model\HeliosRetourSQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosAnalyseFichierRecuTest extends S2lowTestCase
{
    /** @var HeliosDirectoriesManager  */
    private HeliosDirectoriesManager $heliosDirectoriesManager;
    /** @var  HeliosTransactionsSQL */
    private $heliosTransactionSQL;

    public function setUp(): void
    {
        parent::setUp();

        $this->heliosDirectoriesManager = new HeliosDirectoriesManager();
        $this->heliosDirectoriesManager->createDirectories();
        $this->heliosTransactionSQL = self::getContainer()->get(HeliosTransactionsSQL::class);
    }

    public function tearDown(): void
    {
        $this->heliosDirectoriesManager->clearDirectories();
        parent::tearDown();
    }

    public function testGetAllDirectoryVide()
    {
        $filesInDirectory = $this->getHeliosAnalyseFichierReponse()
                                ->getAllDirectory($this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path);
        $this->assertEquals([], $filesInDirectory);

        $this->assertTrue(
            $this->testHandler->hasRecord(
                "Aucun fichier à analyser",
                Level::Info
            )
        );
    }

    public function testGetAllDirectoryDeuxFichiers()
    {
        file_put_contents(
            $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/pes_acquit.xml",
            file_get_contents(__DIR__ . "/fixtures/pes_acquit.xml")
        );
        file_put_contents(
            $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/pes_retour.xml",
            file_get_contents(__DIR__ . "/fixtures/pes_acquit.xml")
        );

        $filesInDirectory = $this->getHeliosAnalyseFichierReponse()
            ->getAllDirectory($this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path);

        $this->assertTrue(in_array("pes_acquit.xml", $filesInDirectory));
        $this->assertTrue(in_array("pes_retour.xml", $filesInDirectory));
        $this->assertEquals(2, count($filesInDirectory));
    }

    private function analyse(string $filename, $response_root = null)
    {
        if (is_null($response_root)) {
            $response_root = $this->heliosDirectoriesManager->helios_response_root;
        }
        $this->getHeliosAnalyseFichierReponse()->analyseOneFileForWorker(
            $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path,
            $response_root,
            $this->heliosDirectoriesManager->helios_responses_error_path,
            $this->heliosDirectoriesManager->helios_ocre,
            $filename
        );
    }

    private function getHeliosAnalyseFichierReponse(): HeliosAnalyseFichierRecu
    {
        $heliosTransactionsSQL = self::getContainer()->get(HeliosTransactionsSQL::class);
        $heliosRetourSQL = self::getContainer()->get(HeliosRetourSQL::class);
        $authoritySiretSQL = self::getContainer()->get(AuthoritySiretSQL::class);
        ;
        $schema_pes_path = HELIOS_XSD_PATH;
        return new HeliosAnalyseFichierRecu(
            $heliosTransactionsSQL,
            $heliosRetourSQL,
            $authoritySiretSQL,
            $schema_pes_path,
            "noreply@sigmalis.com",
            "noreply@sigmalis.com",
            $this->s2lowLogger
        );
    }

    public function testAnalysePesRetour()
    {
        $authoritySireSQL = self::getContainer()->get(AuthoritySiretSQL::class);
        ;
        $authoritySireSQL->add(1, "12345678900035");

        $this->analysePesRetour(__DIR__ . "/fixtures/pes_retour.xml");
        $this->assertTrue(
            $this->testHandler->hasRecordThatMatches(
                "#Traitement de /tmp/phpunit.*/helios_ftp_response_tmp_local_path/pes_retour.xml#",
                Level::Info
            )
        );

        $this->assertTrue(file_exists($this->heliosDirectoriesManager->helios_response_root . "/pes_retour.xml"));
        $heliosRetourSQL = self::getContainer()->get(HeliosRetourSQL::class);
        $info = $heliosRetourSQL->getInfoFromFilename(1, "pes_retour.xml");
        $this->assertEquals("12345678900035", $info['siret']);
    }

    public function testAnalysePesRetourErreurRename()
    {
        $authoritySireSQL = self::getContainer()->get(AuthoritySiretSQL::class);
        ;
        $authoritySireSQL->add(1, "12345678900035");

        $this->analysePesRetour(__DIR__ . "/fixtures/pes_retour.xml", "/Rep/qui/existe/pas");
        $this->assertTrue(
            $this->testHandler->hasRecordThatMatches(
                "#Traitement de /tmp/phpunit.*/helios_ftp_response_tmp_local_path/pes_retour.xml annulé : déplacement impossible#",
                Level::Error
            )
        );

        $this->assertFalse(file_exists($this->heliosDirectoriesManager->helios_response_root . "/pes_retour.xml"));
        $this->assertTrue(file_exists($this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/pes_retour.xml"));
    }

    private function analysePesRetour($file_path, $response_root = null)
    {
        $filename = basename($file_path);
        copy($file_path, $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/$filename");
        $this->analyse($filename, $response_root);
    }

    public function testAnalysePesRetourNonAbonne()
    {
        //$this->expectOutputRegex("#La collectivité 66920145100015 n'est pas abonnée à l'application Comptabilité Publique du TdT#");
        $this->analysePesRetour(__DIR__ . "/fixtures/pes_retour_nonabonne.xml");
        $this->assertFalse(file_exists($this->heliosDirectoriesManager->helios_response_root . "/pes_retour_nonabonne.xml"));

        $this->assertTrue(
            $this->testHandler->hasRecord(
                "[ERREUR] La collectivité 66920145100015 n'est pas abonnée à l'application Comptabilité Publique du TdT, elle n'est donc pas autorisée à recevoir le PES_Retour ",
                Level::Error
            )
        );
    }

    public function testAnalyseDeplacementErreurImpossible()
    {
        copy(__DIR__ . "/fixtures/pes_retour_nonabonne.xml", $this->heliosDirectoriesManager->helios_responses_error_path . "/pes_retour_nonabonne.xml");

        $this->analysePesRetour(__DIR__ . "/fixtures/pes_retour_nonabonne.xml");
        $this->assertTrue(file_exists($this->heliosDirectoriesManager->helios_responses_error_path . "/pes_retour_nonabonne.xml"));
        $this->assertTrue(file_exists($this->heliosDirectoriesManager->helios_responses_error_path . "/pes_retour_nonabonne.xml.1"));

        $this->assertTrue(
            $this->testHandler->hasRecord(
                "[WARNING] Le fichier pes_retour_nonabonne.xml existe déjà dans le répertoire des fichiers en erreur : renommé en *.1",
                Level::Warning
            )
        );
    }

    public function testAnalysePesRetourDoubleSire()
    {
        $authoritySireSQL = self::getContainer()->get(AuthoritySiretSQL::class);
        ;
        $authoritySireSQL->add(1, "12345678900035");
        $authoritySireSQL->add(2, "12345678900035");
        $this->analysePesRetour(__DIR__ . "/fixtures/pes_retour.xml");
        $this->assertFalse(file_exists($this->heliosDirectoriesManager->helios_response_root . "/pes_retour.xml"));

        $this->assertTrue(
            $this->testHandler->hasRecord(
                "[ERREUR] Le SIRET 12345678900035 est associé à plusieurs collectivités. Le PES_Retour n'est donc pas attribué",
                Level::Error
            )
        );
    }

    public function testOcre()
    {
        $filename = "toto.ocre";
        file_put_contents($this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/$filename", "test");
        $this->analyse($filename);
        $this->assertTrue(file_exists($this->heliosDirectoriesManager->helios_ocre . "/" . $filename));

        $this->assertTrue(
            $this->testHandler->hasRecordThatMatches(
                "#Traitement de /tmp/phpunit.*/helios_ftp_response_tmp_local_path/toto.ocre#i",
                Level::Info
            )
        );
    }

    public function testPesAcquitNotFound()
    {
        $filename = "pes_acquit.xml";
        file_put_contents(
            $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/$filename",
            file_get_contents(__DIR__ . "/fixtures/pes_acquit.xml")
        );
        $this->analyse($filename);

        $this->assertTrue(
            $this->testHandler->hasRecord(
                "[ERREUR] L'identificant NomFic pescg291201703030412001 n'est associé à aucune transaction dans la base de données",
                Level::Error
            )
        );
    }

    public function testPesAcquit()
    {
        $transaction_id = $this->createPESAller();
        $filename = "pes_acquit.xml";
        file_put_contents(
            $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/$filename",
            file_get_contents(__DIR__ . "/fixtures/pes_acquit.xml")
        );
        $this->analyse($filename);
        $this->assertTrue(
            $this->testHandler->hasRecord(
                "Transaction $transaction_id : information disponible",
                Level::Info
            )
        );
        $heliosTransactionSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);
        $this->assertEquals(
            HeliosTransactionsSQL::INFORMATION_DISPONIBLE,
            $heliosTransactionSQL->getLatestStatusId($transaction_id)
        );
    }

    public function testPesAcquitErreurRename()
    {
        $this->createPESAller();
        $filename = "pes_acquit.xml";
        file_put_contents(
            $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/$filename",
            file_get_contents(__DIR__ . "/fixtures/pes_acquit.xml")
        );
        $this->analyse($filename, "/repertoire/Non/Existant");
        $this->assertTrue(
            $this->testHandler->hasRecordThatMatches(
                "#Traitement.*/helios_ftp_response_tmp_local_path/pes_acquit.xml annulé : déplacement impossible#",
                Level::Error
            )
        );
        $this->assertFalse(file_exists($this->heliosDirectoriesManager->helios_response_root . "/pes_acquit.xml"));
        $this->assertTrue(file_exists($this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/pes_acquit.xml"));
    }

    private function createPESAller()
    {
        $heliosTransactionSQL = self::getContainer()->get(HeliosTransactionsSQL::class);
        $transaction_id = $heliosTransactionSQL->create("toto", "xxx", 13, 1, 42, "123");

        $info  = array(
            'nom_fic' => "pescg291201703030412001",
            'cod_col' => 400,
            'cod_bud' => 01,
            'id_post' => "086016",
        );

        $heliosTransactionSQL->setInfoFromPESAller($transaction_id, $info);
        $heliosTransactionSQL->updateStatus($transaction_id, HeliosTransactionsSQL::TRANSMIS, "test");
        return $transaction_id;
    }

    private function recupPESAcquit($expectedLastLogMessage)
    {
        $filename = "pes_acquit.xml";
        file_put_contents(
            $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/$filename",
            file_get_contents(__DIR__ . "/fixtures/pes_acquit.xml")
        );
        $this->analyse($filename);

        $this->assertTrue(
            $this->testHandler->hasRecord(
                $expectedLastLogMessage,
                Level::Info
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testPesAcquitDeuxPES()
    {
        $transaction_id_1 = $this->createPESAller();
        $transaction_id_2 = $this->createPESAller();

        $sql = "UPDATE helios_transactions_workflow SET date=? WHERE transaction_id=?";
        $this->getSQLQuery()->query($sql, "1970-01-01", $transaction_id_1);

        $this->recupPESAcquit("Transaction {$transaction_id_2} : information disponible");
    }

    /**
     * @throws Exception
     */
    public function testPesAcquitDeuxPESBefore()
    {
        $transaction_id_1 = $this->createPESAller();
        $transaction_id_2 = $this->createPESAller();

        $sql = "UPDATE helios_transactions_workflow SET date=? WHERE transaction_id=?";
        $this->getSQLQuery()->query($sql, "1970-01-01", $transaction_id_2);

        $this->recupPESAcquit("Transaction {$transaction_id_1} : information disponible");
    }

    public function testPesAcquitDeuxPESUnTransmis()
    {
        $transaction_id_1 = $this->createPESAller();
        $transaction_id_2 = $this->createPESAller();
        $this->heliosTransactionSQL->updateStatus(
            $transaction_id_1,
            HeliosTransactionsSQL::ACQUITTE,
            "test"
        );
        $this->recupPESAcquit("Transaction {$transaction_id_2} : information disponible");
    }

    public function testPesAcquitDeuxPESUnTransmisDeux()
    {
        $transaction_id_1 = $this->createPESAller();
        $transaction_id_2 = $this->createPESAller();
        $this->heliosTransactionSQL->updateStatus(
            $transaction_id_2,
            HeliosTransactionsSQL::ACQUITTE,
            "test"
        );

        $this->recupPESAcquit("Transaction {$transaction_id_1} : information disponible");
    }

    /**
     * @throws Exception
     */
    public function testMalformedAcquit()
    {
        $transaction_id = $this->createPESAller();
        $filename = "pes_acquit_not_valid.xml";
        file_put_contents(
            $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/$filename",
            file_get_contents(__DIR__ . "/fixtures/pes_acquit_not_valid.xml")
        );

        $this->getHeliosAnalyseFichierReponse()->analyseOneFile(
            $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/$filename",
            $this->heliosDirectoriesManager->helios_response_root,
            $this->heliosDirectoriesManager->helios_ocre,
            HELIOS_XSD_PATH
        );

        $this->assertTrue(
            $this->testHandler->hasRecord(
                "Transaction $transaction_id : erreur retournée par Helios",
                Level::Info
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testMalformedAcquitErreurRename()
    {
        $transaction_id = $this->createPESAller();
        $filename = "pes_acquit_not_valid.xml";
        file_put_contents(
            $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/$filename",
            file_get_contents(__DIR__ . "/fixtures/pes_acquit_not_valid.xml")
        );

        $this->getHeliosAnalyseFichierReponse()->analyseOneFile(
            $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/$filename",
            "/Un/Rep/Inexistant",
            $this->heliosDirectoriesManager->helios_ocre,
            HELIOS_XSD_PATH
        );

        $this->assertTrue(
            $this->testHandler->hasRecordThatMatches(
                "#Trai.*/helios_ftp_response_tmp_local_path//pes_acquit_not_valid.xml annulé : déplacement impossible#",
                Level::Error
            )
        );
    }

    public function testMalformaedAcquitWithoutCodCol()
    {
        $transaction_id = $this->createPESAller();
        $filename = "pes_acquit_not_valid_without_cod_col.xml";
        file_put_contents(
            $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/$filename",
            file_get_contents(__DIR__ . "/fixtures/pes_acquit_not_valid_without_cod_col.xml")
        );
        $this->getHeliosAnalyseFichierReponse()->analyseOneFile(
            $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/$filename",
            $this->heliosDirectoriesManager->helios_response_root,
            $this->heliosDirectoriesManager->helios_ocre,
            HELIOS_XSD_PATH
        );

        $this->assertTrue(
            $this->testHandler->hasRecord(
                "Transaction $transaction_id : erreur retournée par Helios",
                Level::Info
            )
        );
    }

    public function testBadRenamePesAcquit()
    {
        $heliosTransactionSQL = self::getContainer()->get(HeliosTransactionsSQL::class);
        $transaction_id = $heliosTransactionSQL->create(
            "toto",
            "xxx",
            13,
            1,
            42,
            "123"
        );

        $info = array(
            'nom_fic' => "pescg291201703030412001",
            'cod_col' => 400,
            'cod_bud' => 01,
            'id_post' => "086016",
        );

        $heliosTransactionSQL->setInfoFromPESAller($transaction_id, $info);
        $heliosTransactionSQL->updateStatus($transaction_id, HeliosTransactionsSQL::TRANSMIS, "test");

        $filename = "pes_acquit.xml";
        file_put_contents(
            $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/$filename",
            file_get_contents(__DIR__ . "/fixtures/pes_acquit.xml")
        );
        $this->analyse($filename, "Nim/Por/Te/Quoi");
        $this->assertTrue(
            $this->testHandler->hasRecordThatMatches(
                "#Traitement de .* annulé : déplacement impossible#",
                Level::Error
            )
        );
    }
}
