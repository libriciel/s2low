<?php

use Monolog\Level;
use PHPUnit\helios\PesTestFile;
use Psr\Log\LoggerInterface;
use S2low\Infrastructure\Directory;
use S2low\Services\FilesAndDirectoriesUtils\DirectoryScanner;
use S2lowLegacy\Class\helios\IncomingFileProcessor;
use S2lowLegacy\Model\AuthoritySiretSQL;
use S2lowLegacy\Model\HeliosRetourSQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosAnalyseFichierRecuTest extends S2lowTestCase
{
    /** @var HeliosDirectoriesManager  */
    private HeliosDirectoriesManager $heliosDirectoriesManager;

    /** @var  HeliosTransactionsSQL */
    private HeliosTransactionsSQL $heliosTransactionSQL;

    private Directory $incomingDirectory;

    private IncomingFileProcessor $incomingFileProcessor;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->heliosDirectoriesManager = new HeliosDirectoriesManager();
        $this->heliosDirectoriesManager->createDirectories();

        self::getContainer()->set(LoggerInterface::class, $this->logger);

        $this->incomingDirectory = new Directory(
            $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path,
        );

        self::getContainer()->set(
            'app.heliosFilesIncoming',
            $this->incomingDirectory
        );

        self::getContainer()->set(
            'app.heliosResponseDirectory',
            new Directory(
                $this->heliosDirectoriesManager->helios_response_root
            )
        );

        self::getContainer()->set(
            'app.heliosErrorDirectory',
            new Directory(
                $this->heliosDirectoriesManager->helios_responses_error_path
            )
        );

        self::getContainer()->set(
            'app.heliosOcreDirectory',
            new Directory(
                $this->heliosDirectoriesManager->helios_ocre
            )
        );

        $this->heliosTransactionSQL = self::getContainer()->get(HeliosTransactionsSQL::class);
        $this->incomingFileProcessor = self::getContainer()->get(IncomingFileProcessor::class);
    }

    public function tearDown(): void
    {
        $this->heliosDirectoriesManager->clearDirectories();
        parent::tearDown();
    }

    public function testGetAllDirectoryVide()
    {
        $directory = self::getContainer()->get('app.heliosResponseDirectory');
        $directoryScanner = self::getContainer()->get(DirectoryScanner::class);

        static::assertEquals([], $directoryScanner->getFileNames($directory));

        static::assertTrue(
            $this->testHandler->hasRecord(
                'Aucun fichier à analyser',
                Level::Info
            )
        );
    }

    public function testGetAllDirectoryDeuxFichiers()
    {
        $this->heliosDirectoriesManager->putInTmpDirectory(PesTestFile::PES_RETOUR);
        $this->heliosDirectoriesManager->putInTmpDirectory(PesTestFile::PES_ACQUIT);

        $heliosResponseDirectory =
            new Directory(
                $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path
            );

        $filesInDirectory = scandir($heliosResponseDirectory->getPath());


        static::assertTrue(in_array(PesTestFile::PES_ACQUIT->value, $filesInDirectory));
        static::assertTrue(in_array(PesTestFile::PES_RETOUR->value, $filesInDirectory));
        static::assertEquals(4, count($filesInDirectory));
    }

    /**
     * @throws \Exception
     */
    public function testAnalysePesRetour()
    {
        $authoritySireSQL = self::getContainer()->get(AuthoritySiretSQL::class);
        $authoritySireSQL->add(1, '12345678900035');

        $this->heliosDirectoriesManager->putInTmpDirectory(PesTestFile::PES_RETOUR);

        $this->incomingFileProcessor->process(
            new SplFileObject(
                $this->incomingDirectory->getPath(PesTestFile::PES_RETOUR->getFilename())
            )
        );

        static::assertTrue(
            $this->testHandler->hasRecordThatMatches(
                '#Traitement de /tmp/phpunit.*/helios_ftp_response_tmp_local_path/pes_retour.xml#',
                Level::Info
            )
        );

        static::assertTrue($this->heliosDirectoriesManager->isInResponseDirectory(PesTestFile::PES_RETOUR));
        $heliosRetourSQL = self::getContainer()->get(HeliosRetourSQL::class);
        $info = $heliosRetourSQL->getInfoFromFilename(1, PesTestFile::PES_RETOUR->getFilename());
        static::assertEquals('12345678900035', $info['siret']);
    }

    /**
     * @throws \Exception
     */
    public function testAnalysePesAcquitWrongXSDNotLinkedToTransaction()
    {
        $authoritySireSQL = self::getContainer()->get(AuthoritySiretSQL::class);
        $authoritySireSQL->add(1, '12345678900035');

        $this->heliosDirectoriesManager->putInTmpDirectory(PesTestFile::PES_ACQUIT_NOT_VALID_NOT_LINKED_TO_TRANSACTION);

        $this->incomingFileProcessor->process(
            new SplFileObject(
                $this->incomingDirectory->getPath(PesTestFile::PES_ACQUIT_NOT_VALID_NOT_LINKED_TO_TRANSACTION->getFilename())
            )
        );

        static::assertTrue(
            $this->testHandler->hasRecordThatMatches(
                '#Erreur lors de la validation du schéma XML#',
                Level::Error
            )
        );

        static::assertTrue($this->heliosDirectoriesManager->isInErrorDirectory(PesTestFile::PES_ACQUIT_NOT_VALID_NOT_LINKED_TO_TRANSACTION));
        static::assertFalse($this->heliosDirectoriesManager->isInTmpDirectory(PesTestFile::PES_ACQUIT_NOT_VALID_NOT_LINKED_TO_TRANSACTION));
        static::assertFalse($this->heliosDirectoriesManager->isInResponseDirectory(PesTestFile::PES_ACQUIT_NOT_VALID_NOT_LINKED_TO_TRANSACTION));
    }

    public function testAnalyseWrongXSDNotLinkedToTransaction()
    {
        $authoritySireSQL = self::getContainer()->get(AuthoritySiretSQL::class);
        $authoritySireSQL->add(1, '12345678900035');

        $this->heliosDirectoriesManager->putInTmpDirectory(PesTestFile::WRONG_ROOT_NOT_LINKED_TO_TRANSACTION);

        $this->incomingFileProcessor->process(
            new SPLFileObject(
                $this->incomingDirectory->getPath(PesTestFile::WRONG_ROOT_NOT_LINKED_TO_TRANSACTION->getFilename())
            )
        );
        static::assertTrue(
            $this->testHandler->hasRecordThatMatches(
                '#No available strategy to handle PES document#',
                Level::Error
            )
        );

        static::assertTrue($this->heliosDirectoriesManager->isInErrorDirectory(PesTestFile::WRONG_ROOT_NOT_LINKED_TO_TRANSACTION));
        static::assertFalse($this->heliosDirectoriesManager->isInTmpDirectory(PesTestFile::WRONG_ROOT_NOT_LINKED_TO_TRANSACTION));
        static::assertFalse($this->heliosDirectoriesManager->isInResponseDirectory(PesTestFile::WRONG_ROOT_NOT_LINKED_TO_TRANSACTION));
    }

    /**
     * @throws \Exception
     */
    public function testAnalysePesRetourErreurRename()
    {
        $authoritySireSQL = self::getContainer()->get(AuthoritySiretSQL::class);
        $authoritySireSQL->add(1, '12345678900035');

        $this->heliosDirectoriesManager->putInTmpDirectory(PesTestFile::PES_RETOUR);

        $this->heliosDirectoriesManager->setResponseRootToUnwritable();

        $this->incomingFileProcessor->process(
            new SPLFileObject(
                $this->incomingDirectory->getPath(PesTestFile::PES_RETOUR->getFilename())
            )
        );

        static::assertTrue(
            $this->testHandler->hasRecordThatMatches(
                '#Échec du traitement du fichier#',
                Level::Error
            )
        );
        static::assertFalse($this->heliosDirectoriesManager->isInResponseDirectory(PesTestFile::PES_RETOUR));
        //static::assertTrue($this->heliosDirectoriesManager->isInTmpDirectory(PesTestFile::PES_RETOUR));
        static::assertFalse($this->heliosDirectoriesManager->isInErrorDirectory(PesTestFile::PES_RETOUR));
    }

    /**
     * @throws \Exception
     */
    public function testAnalysePesRetourNonAbonne(): void
    {
        $this->heliosDirectoriesManager->putInTmpDirectory(PesTestFile::PES_RETOUR_NON_ABONNE);

        $this->incomingFileProcessor->process(
            new SPLFileObject(
                $this->incomingDirectory->getPath(PesTestFile::PES_RETOUR_NON_ABONNE->getFilename())
            )
        );

        static::assertFalse($this->heliosDirectoriesManager->isInResponseDirectory(PesTestFile::PES_RETOUR_NON_ABONNE));
        static::assertTrue($this->heliosDirectoriesManager->isInErrorDirectory(PesTestFile::PES_RETOUR_NON_ABONNE));

        static::assertTrue(
            $this->testHandler->hasRecord(
                "La collectivité 66920145100015 n'est pas abonnée à l'application Comptabilité Publique du TdT, elle n'est donc pas autorisée à recevoir le PES_Retour ",
                Level::Error
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function testAnalyseDeplacementErreurImpossible()
    {

        $this->heliosDirectoriesManager->putInTmpDirectory(PesTestFile::PES_RETOUR_NON_ABONNE);
        $this->heliosDirectoriesManager->putInErrorDirectory(PesTestFile::PES_RETOUR_NON_ABONNE);

        $this->heliosDirectoriesManager->setResponseRootToUnwritable();

        $this->incomingFileProcessor->process(
            new SPLFileObject(
                $this->incomingDirectory->getPath(PesTestFile::PES_RETOUR_NON_ABONNE->getFilename())
            )
        );

        static::assertTrue($this->heliosDirectoriesManager->isInErrorDirectory(PesTestFile::PES_RETOUR_NON_ABONNE));
        static::assertTrue(file_exists($this->heliosDirectoriesManager->helios_responses_error_path . '/pes_retour_nonabonne.xml.1'));

        static::assertTrue(
            $this->testHandler->hasRecord(
                '[WARNING] Le fichier pes_retour_nonabonne.xml existe déjà dans le répertoire des fichiers en erreur : renommé en *.1',
                Level::Warning
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function testAnalysePesRetourDoubleSire()
    {
        $authoritySireSQL = self::getContainer()->get(AuthoritySiretSQL::class);

        $authoritySireSQL->add(1, '12345678900035');
        $authoritySireSQL->add(2, '12345678900035');

        $this->heliosDirectoriesManager->putInTmpDirectory(PesTestFile::PES_RETOUR);

        $this->incomingFileProcessor->process(
            new SPLFileObject(
                $this->incomingDirectory->getPath(PesTestFile::PES_RETOUR->getFilename())
            )
        );

        static::assertFalse($this->heliosDirectoriesManager->isInResponseDirectory(PesTestFile::PES_RETOUR));
        static::assertTrue($this->heliosDirectoriesManager->isInErrorDirectory(PesTestFile::PES_RETOUR));

        static::assertTrue(
            $this->testHandler->hasRecord(
                "Le SIRET 12345678900035 est associé à plusieurs collectivités. Le PES_Retour n'est donc pas attribué",
                Level::Error
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function testOcre()
    {
        $filename = 'toto.ocre';
        file_put_contents($this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/$filename", 'test');

        $this->incomingFileProcessor->process(
            new SPLFileObject(
                $this->incomingDirectory->getPath($filename)
            )
        );
        static::assertTrue(file_exists($this->heliosDirectoriesManager->helios_ocre . '/' . $filename));

        static::assertTrue(
            $this->testHandler->hasRecordThatMatches(
                '#Traitement de /tmp/phpunit.*/helios_ftp_response_tmp_local_path/toto.ocre#i',
                Level::Info
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function testTextFile()
    {
        $this->heliosDirectoriesManager->putInTmpDirectory(PesTestFile::NOT_XML);

        $this->incomingFileProcessor->process(
            new SPLFileObject(
                $this->incomingDirectory->getPath(PesTestFile::NOT_XML->getFilename())
            )
        );

        static::assertTrue(
            $this->testHandler->hasRecordThatMatches(
                '#Aucun handler compatible pour#',
                Level::Error
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function testPesAcquitNotFound()
    {
        $this->heliosDirectoriesManager->putInTmpDirectory(PesTestFile::PES_ACQUIT);

        $this->incomingFileProcessor->process(
            new SPLFileObject(
                $this->incomingDirectory->getPath(PesTestFile::PES_ACQUIT->getFilename())
            )
        );

        static::assertTrue(
            $this->testHandler->hasRecord(
                "Le couple NomFic pescg291201703030412001 et CodCol 400 n'est associé à aucune transaction dans la base de données",
                Level::Error
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function testPesAcquit()
    {
        $transaction_id = $this->createPESAller();
        $this->heliosDirectoriesManager->putInTmpDirectory(PesTestFile::PES_ACQUIT);

        $this->incomingFileProcessor->process(
            new SPLFileObject(
                $this->incomingDirectory->getPath(PesTestFile::PES_ACQUIT->getFilename())
            )
        );

        static::assertTrue(
            $this->testHandler->hasRecord(
                "Transaction $transaction_id : information disponible",
                Level::Info
            )
        );
        $heliosTransactionSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);
        static::assertEquals(
            HeliosTransactionsSQL::INFORMATION_DISPONIBLE,
            $heliosTransactionSQL->getLatestStatusId($transaction_id)
        );
    }

    /**
     * @throws \Exception
     */
    public function testPesAcquitErreurRename()
    {
        $this->createPESAller();
        $this->heliosDirectoriesManager->putInTmpDirectory(PesTestFile::PES_ACQUIT);

        $this->heliosDirectoriesManager->setResponseRootToUnwritable();

        $this->incomingFileProcessor->process(
            new SPLFileObject(
                $this->incomingDirectory->getPath(PesTestFile::PES_ACQUIT->getFilename())
            )
        );

        static::assertTrue(
            $this->testHandler->hasRecordThatMatches(
                '#Échec du traitement du fichier#',
                Level::Error
            )
        );
        static::assertFalse($this->heliosDirectoriesManager->isInResponseDirectory(PesTestFile::PES_ACQUIT));
        static::assertTrue($this->heliosDirectoriesManager->isInTmpDirectory(PesTestFile::PES_ACQUIT));
    }

    private function createPESAller()
    {
        $heliosTransactionSQL = self::getContainer()->get(HeliosTransactionsSQL::class);
        $transaction_id = $heliosTransactionSQL->create('toto', 'xxx', 13, 1, 42, '123');

        $info = [
            'nom_fic' => 'pescg291201703030412001',
            'cod_col' => 400,
            'cod_bud' => 01,
            'id_post' => '086016',
        ];

        $heliosTransactionSQL->setInfoFromPESAller($transaction_id, $info);
        $heliosTransactionSQL->updateStatus($transaction_id, HeliosTransactionsSQL::TRANSMIS, 'test');
        return $transaction_id;
    }

    /**
     * @throws \Exception
     */
    private function recupPESAcquit($expectedLastLogMessage): void
    {
        $this->heliosDirectoriesManager->putInTmpDirectory(PesTestFile::PES_ACQUIT);

        $this->incomingFileProcessor->process(
            new SPLFileObject(
                $this->incomingDirectory->getPath(PesTestFile::PES_ACQUIT->getFilename())
            )
        );

        static::assertTrue(
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

        $sql = 'UPDATE helios_transactions_workflow SET date=? WHERE transaction_id=?';
        $this->getSQLQuery()->query($sql, '1970-01-01', $transaction_id_1);

        $this->recupPESAcquit("Transaction $transaction_id_2 : information disponible");
    }

    /**
     * @throws Exception
     */
    public function testPesAcquitDeuxPESBefore()
    {
        $transaction_id_1 = $this->createPESAller();
        $transaction_id_2 = $this->createPESAller();

        $sql = 'UPDATE helios_transactions_workflow SET date=? WHERE transaction_id=?';
        $this->getSQLQuery()->query($sql, '1970-01-01', $transaction_id_2);

        $this->recupPESAcquit("Transaction $transaction_id_1 : information disponible");
    }

    /**
     * @throws \Exception
     */
    public function testPesAcquitDeuxPESUnTransmis()
    {
        $transaction_id_1 = $this->createPESAller();
        $transaction_id_2 = $this->createPESAller();
        $this->heliosTransactionSQL->updateStatus(
            $transaction_id_1,
            HeliosTransactionsSQL::ACQUITTE,
            'test'
        );
        $this->recupPESAcquit("Transaction $transaction_id_2 : information disponible");
    }

    /**
     * @throws \Exception
     */
    public function testPesAcquitDeuxPESUnTransmisDeux()
    {
        $transaction_id_1 = $this->createPESAller();
        $transaction_id_2 = $this->createPESAller();
        $this->heliosTransactionSQL->updateStatus(
            $transaction_id_2,
            HeliosTransactionsSQL::ACQUITTE,
            'test'
        );

        $this->recupPESAcquit("Transaction $transaction_id_1 : information disponible");
    }

    /**
     * @throws Exception
     */
    public function testMalformedAcquit()
    {
        $transaction_id = $this->createPESAller();
        $this->heliosDirectoriesManager->putInTmpDirectory(PesTestFile::PES_ACQUIT_NOT_VALID);

        $this->incomingFileProcessor->process(
            new SPLFileObject(
                $this->incomingDirectory->getPath(PesTestFile::PES_ACQUIT_NOT_VALID->getFilename())
            )
        );

        static::assertTrue(
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
        $this->createPESAller();
        $this->heliosDirectoriesManager->putInTmpDirectory(PesTestFile::PES_ACQUIT_NOT_VALID);

        $this->heliosDirectoriesManager->setResponseRootToUnwritable();

        $this->incomingFileProcessor->process(
            new SPLFileObject(
                $this->incomingDirectory->getPath(PesTestFile::PES_ACQUIT_NOT_VALID->getFilename())
            )
        );

        static::assertTrue(
            $this->testHandler->hasRecordThatMatches(
                '#Échec du traitement du fichier#',
                Level::Error
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function testMalformedAcquitWithoutCodCol()
    {
        $transaction_id = $this->createPESAller();
        $this->heliosDirectoriesManager->putInTmpDirectory(PesTestFile::PES_ACQUIT_NOT_VALID_WITHOUT_COD_COL);

        $this->incomingFileProcessor->process(
            new SPLFileObject(
                $this->incomingDirectory->getPath(PesTestFile::PES_ACQUIT_NOT_VALID_WITHOUT_COD_COL->getFilename())
            )
        );
        static::assertTrue(
            $this->testHandler->hasRecord(
                "Transaction $transaction_id : erreur retournée par Helios",
                Level::Info
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function testBadRenamePesAcquit()
    {
        $heliosTransactionSQL = self::getContainer()->get(HeliosTransactionsSQL::class);
        $transaction_id = $heliosTransactionSQL->create(
            'toto',
            'xxx',
            13,
            1,
            42,
            '123'
        );

        $info = [
            'nom_fic' => 'pescg291201703030412001',
            'cod_col' => 400,
            'cod_bud' => 01,
            'id_post' => '086016',
        ];

        $heliosTransactionSQL->setInfoFromPESAller($transaction_id, $info);
        $heliosTransactionSQL->updateStatus($transaction_id, HeliosTransactionsSQL::TRANSMIS, 'test');

        $this->heliosDirectoriesManager->putInTmpDirectory(PesTestFile::PES_ACQUIT);
        $this->heliosDirectoriesManager->setResponseRootToUnwritable();

        $this->incomingFileProcessor->process(
            new SPLFileObject(
                $this->incomingDirectory->getPath(PesTestFile::PES_ACQUIT->getFilename())
            )
        );

        static::assertTrue(
            $this->testHandler->hasRecordThatMatches(
                '#Échec du traitement du fichier#',
                Level::Error
            )
        );
    }
}
