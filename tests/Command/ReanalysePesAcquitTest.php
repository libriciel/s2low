<?php

declare(strict_types=1);

namespace S2low\Tests\Command;

use Exception;
use org\bovigo\vfs\vfsStream;
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

class ReanalysePesAcquitTest extends KernelTestCase
{
    protected function setUp(): void
    {
        LegacyObjectsManager::resetObjectInstancier();
        self::ensureKernelShutdown();

        // WARNING : Normalement, la gestion des répertoires tmp est géré par vfs ...
        $tmpFolder = new TmpFolder();
        $helios_responses_root = $tmpFolder->create();
        $this->helios_files_upload_root = $tmpFolder->create();
        // WARNING : fin de la partie à remplacer

        LegacyObjectsManager::getLegacyObjectInstancier()->set(
            'helios_responses_root',
            $helios_responses_root
        );

        LegacyObjectsManager::getLegacyObjectInstancier()->set(
            'helios_files_upload_root',
            $this->helios_files_upload_root
        );

        $kernel = new Kernel('test', true);
        $application = new Application($kernel);

        $command = $application->find('helios:reanalyse-pes-acquit');
        $this->commandTester = new CommandTester($command);
    }
    public function testExecuteOnATransactionWithoutPesAcquit()
    {
        $commandOutput = $this->commandTester->execute([
            // pass arguments to the helper
            'transaction-id' => 1
        ]);

        static::assertStringContainsString("[1] Path '' vide", $this->commandTester->getDisplay());
        static::assertEquals(-1, $commandOutput);
    }

    /**
     * @throws Exception
     */
    public function testExecuteOnATransactionWithPesAcquit()
    {
        $pes_aller = __DIR__ . '/../../test/PHPUnit/helios/fixtures/pes_aller_ok.xml';
        $pes_acquit = __DIR__ . '/../../test/PHPUnit/helios/fixtures/pes_acquit.xml';

        $pesAllerRetriever = new PesAllerRetriever(
            $this->helios_files_upload_root,
            $this->getObjectInstancier()->get(OpenStackSwiftWrapper::class),
            $this->getObjectInstancier()->get(S2lowLogger::class)
        )
            ;
        $filepath = $pesAllerRetriever->getPathForNonExistingFile(sha1_file($pes_aller));
        vfsStream::setup('test');
        copy($pes_aller, $filepath);
        /** @var HeliosController $heliosControler */
        $heliosControler = $this->getObjectInstancier()->get(HeliosController::class);
        $transaction_id =  $heliosControler->importFile(1, $pes_aller, 'pes_aller.xml');

        copy($pes_acquit, $this->getObjectInstancier()->get('helios_responses_root') . '/pes_acquit.xml');

        $heliosTransactionSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);

        $heliosTransactionSQL->setAcquitFilename($transaction_id, 'pes_acquit.xml');

        $commandOutput = $this->commandTester->execute([
            // pass arguments to the helper
            'transaction-id' => $transaction_id
        ]);

        static::assertStringContainsString(
            "[$transaction_id] Copie de /data/tdt-workspace/helios/response//pes_acquit.xml " .
            'vers /data/tdt-workspace/helios/response_tmp//pes_acquit.xml',
            $this->commandTester->getDisplay()
        );
        static::assertEquals(0, $commandOutput);
    }

    private function getObjectInstancier(): ObjectInstancier
    {
        return LegacyObjectsManager::getLegacyObjectInstancier();
    }
}
