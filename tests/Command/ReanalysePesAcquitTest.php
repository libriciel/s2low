<?php

namespace S2low\Tests\Command;

use S2low\Helpers\ClassHelper;
use S2low\Kernel;
use S2lowLegacy\Class\helios\PesAllerRetriever;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Controller\HeliosController;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command\Command;
use TestEnvironmentManager;

class ReanalysePesAcquitTest extends KernelTestCase
{
    public function testExecuteOnATransactionWithoutPesAcquit()
    {
        LegacyObjectsManager::resetObjectInstancier();
        self::ensureKernelShutdown();
        $kernel = new Kernel('test', true);
        $application = new Application($kernel);

        $command = $application->find('helios:reanalyse-pes-acquit');
        $commandTester = new CommandTester($command);
        $commandOutput = $commandTester->execute([
            // pass arguments to the helper
            'transaction-id' => '1'
        ]);

        $this->assertStringContainsString("[1] Path \'\' vide", $commandTester->getDisplay());
        $this->assertEquals(-1, $commandOutput);
    }

    public function testExecuteOnATransactionWithPesAcquit()
    {
        LegacyObjectsManager::resetObjectInstancier();
        self::ensureKernelShutdown();

        // ROH LA VACHE, C'EST DEGUEU !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        // On setup l'environnement manager de test ici...                    !!
        // Vérifier s'il vaut mieux le faire ici, après l'init du Kernel ...  !!
        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        $this->testEnvironmentManager = new TestEnvironmentManager();
        $this->testEnvironmentManager->setUp();

        $pes_aller = __DIR__ . '/../../test/PHPUnit/helios/fixtures/pes_aller_ok.xml';
        $pes_acquit = __DIR__ . '/../../test/PHPUnit/helios/fixtures/pes_acquit.xml';

        $kernel = new Kernel('test', true);
        $application = new Application($kernel);

        $helios_files_upload_root = LegacyObjectsManager::getLegacyObjectInstancier()->get('helios_files_upload_root');

        /** @var PesAllerRetriever $pesAllerRetriever */
        $pesAllerRetriever = new PesAllerRetriever(
            $helios_files_upload_root,
            $this->getObjectInstancier()->get(OpenStackSwiftWrapper::class),
            $this->getObjectInstancier()->get(S2lowLogger::class)
        )
            ;
        $filepath = $pesAllerRetriever->getPathForNonExistingFile(sha1_file($pes_aller));

        // TODO : Généraliser la création de répertoires ...
        // Les constantes sont définies, mais les répertoires ne sont pas systématiquement créés.
        mkdir($helios_files_upload_root);
        mkdir($this->getObjectInstancier()->get('helios_responses_root'));
        mkdir($this->getObjectInstancier()->get('helios_ftp_response_tmp_local_path'));

        copy($pes_aller, $filepath);
        $heliosControler = $this->getObjectInstancier()->get(HeliosController::class);
        $transaction_id =  $heliosControler->importFile(8, $pes_aller, "pes_aller.xml");

        // TODO : passer par vfs aussi
        var_dump($this->getObjectInstancier()->get('helios_responses_root'));

        copy($pes_acquit, $this->getObjectInstancier()->get('helios_responses_root') . "/pes_acquit.xml");

        $heliosTransactionSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);

        $heliosTransactionSQL->setAcquitFilename($transaction_id, "pes_acquit.xml");

        $command = $application->find('helios:reanalyse-pes-acquit');
        $commandTester = new CommandTester($command);
        $commandOutput = $commandTester->execute([
            // pass arguments to the helper
            'transaction-id' => $transaction_id
        ]);

        $this->assertStringContainsString("Copie de ", $commandTester->getDisplay());
        $this->assertEquals(0, $commandOutput);
    }

    private function getObjectInstancier(): ObjectInstancier
    {
        return LegacyObjectsManager::getLegacyObjectInstancier();
    }
}
