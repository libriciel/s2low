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

        $this->assertStringContainsString("[1] Path vide, ignoré", $commandTester->getDisplay());
        $this->assertEquals(-1, $commandOutput);
    }

    public function testExecuteOnATransactionWithPesAcquit()
    {
        LegacyObjectsManager::resetObjectInstancier();
        self::ensureKernelShutdown();

        // WARNING : Normalement, la gestion des répertoires tmp est géré par vfs ...
        $tmpFolder = new TmpFolder();
        $helios_responses_root = $tmpFolder->create();
        $helios_files_upload_root = $tmpFolder->create();

        $pes_aller = __DIR__ . '/../../test/PHPUnit/helios/fixtures/pes_aller_ok.xml';
        $pes_acquit = __DIR__ . '/../../test/PHPUnit/helios/fixtures/pes_acquit.xml';
        // WARNING : fin de la partie à remplacer

        LegacyObjectsManager::getLegacyObjectInstancier()->set(
            'helios_responses_root',
            $helios_responses_root
        );

        LegacyObjectsManager::getLegacyObjectInstancier()->set(
            'helios_files_upload_root',
            $helios_files_upload_root
        );

        $kernel = new Kernel('test', true);
        $application = new Application($kernel);

        $command = $application->find('helios:reanalyse-pes-acquit');
        $commandTester = new CommandTester($command);
        $commandOutput = $commandTester->execute([
            // pass arguments to the helper
            'transaction-id' => '1'
        ]);

        /** @var PesAllerRetriever $pesAllerRetriever */
        $pesAllerRetriever = new PesAllerRetriever(
            $helios_files_upload_root,
            $this->getObjectInstancier()->get(OpenStackSwiftWrapper::class),
            $this->getObjectInstancier()->get(S2lowLogger::class)
        )
            ;
        $filepath = $pesAllerRetriever->getPathForNonExistingFile(sha1_file($pes_aller));
        \org\bovigo\vfs\vfsStream::setup('test');
        var_dump($pes_aller);
        var_dump($filepath);
        copy($pes_aller, $filepath);
        $heliosControler = $this->getObjectInstancier()->get(HeliosController::class);
        $transaction_id =  $heliosControler->importFile(8, $pes_aller, "pes_aller.xml");

        copy($pes_acquit, $this->getObjectInstancier()->get('helios_responses_root') . "/pes_acquit.xml");

        $heliosTransactionSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);

        $heliosTransactionSQL->setAcquitFilename($transaction_id, "pes_acquit.xml");

        $this->assertStringContainsString("[1] Path vide, ignoré", $commandTester->getDisplay());
        $this->assertEquals(-1, $commandOutput);
    }

    private function getObjectInstancier(): ObjectInstancier
    {
        return LegacyObjectsManager::getLegacyObjectInstancier();
    }
}
