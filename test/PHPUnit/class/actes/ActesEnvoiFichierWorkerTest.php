<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use S2lowLegacy\Class\actes\ActesEnvoiFichierWorker;
use S2lowLegacy\Class\actes\ActesFileSender;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\ActesWorkspaceForTests;
use S2lowLegacy\Class\IActesWorkspace;
use S2lowLegacy\Class\RecoverableException;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Model\LogsSQL;
use S2lowTestCase;

class ActesEnvoiFichierWorkerTest extends S2lowTestCase
{
    private string $enveloppe_directory;
    private string $siren;
    private ActesWorkspaceForTests $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siren = '491011698';
        $this->workspace = new ActesWorkspaceForTests();
        $this->getObjectInstancier()->set(IActesWorkspace::class, $this->workspace);
        $this->enveloppe_directory = $this->getActesWorkspace()
                ->getFilesUploadRoot() . '/' . $this->siren;
        mkdir($this->enveloppe_directory);

        $actesFileSender = $this->getMockBuilder(ActesFileSender::class)->disableOriginalConstructor()->getMock();
        $this->getObjectInstancier()->set(ActesFileSender::class, $actesFileSender);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->workspace->clear();
    }


    /**
     * @throws RecoverableException
     */
    public function testEnvoiUneEnveloppe()
    {
        $transaction_id = $this->createTransaction(
            ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION,
            __DIR__ . '/../../fixtures/ok/SLO-EACT--214502494--20170717-5.tar.gz'
        );
        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);

        /** @var ActesEnvoiFichierWorker $actesEnvoiFichierController */
        $actesEnvoiFichierController = $this->getObjectInstancier()->get(ActesEnvoiFichierWorker::class);
        $actesEnvoiFichierController->work($transaction_info['envelope_id']);

        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        static::assertSame(ActesStatusSQL::STATUS_TRANSMIS, $transaction_info['last_status_id']);
        $lastTransactionWorkflowInfo = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        static::assertSame(ActesStatusSQL::STATUS_TRANSMIS, $lastTransactionWorkflowInfo['status_id']);
        static::assertSame(
            'Transmis au MI',
            $lastTransactionWorkflowInfo['message']
        );
        $logsSQL = $this->getObjectInstancier()->get(LogsSQL::class);
        $liste = $logsSQL->getLastLog();
        static::assertMatchesRegularExpression("#Transaction.*[0-9]* : passage à l'état transmis#", $liste['message']);
    }

    /**
     * @return void
     */
    public function testEnvoiUneEnveloppeMauvaisEtat()
    {
        $transaction_id = $this->createTransaction(
            ActesStatusSQL::STATUS_TRANSMIS,
            __DIR__ . '/../../fixtures/ok/SLO-EACT--214502494--20170717-5.tar.gz'
        );

        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);

        $envelope_id = $transaction_info['envelope_id'];

        $actesEnvoiFichierController = $this->getObjectInstancier()->get(ActesEnvoiFichierWorker::class);

        $actesEnvoiFichierController->work($envelope_id);


        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        static::assertSame(ActesStatusSQL::STATUS_TRANSMIS, $transaction_info['last_status_id']);
        $lastTransactionWorkflowInfo = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        static::assertSame(ActesStatusSQL::STATUS_TRANSMIS, $lastTransactionWorkflowInfo['status_id']);
        static::assertSame(
            'Creation',
            $lastTransactionWorkflowInfo['message']
        );

        static::assertSame(
            "La transaction $transaction_id à poster n'est pas en attente de transmission : état 3 trouvé",
            $this->getLogRecords()[0]['message']
        );
    }

    public function testEnvoiUneEnveloppeFailed()
    {
        $transaction_id = $this->createTransaction(
            ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION,
            __DIR__ . '/../../fixtures/ok/SLO-EACT--214502494--20170717-5.tar.gz'
        );
        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);

        /** @var MockObject|ActesFileSender $actesFileSender */
        $actesFileSender = $this->getObjectInstancier()->get(ActesFileSender::class);

        $actesFileSender->method('send')->willThrowException(new Exception('Erreur du mock'));

        self::expectException(Exception::class);
        self::expectExceptionMessage('Erreur du mock');
        /** @var ActesEnvoiFichierWorker $actesEnvoiFichierWorker */
        $actesEnvoiFichierWorker = $this->getObjectInstancier()->get(ActesEnvoiFichierWorker::class);
        $actesEnvoiFichierWorker->work($transaction_info['envelope_id']);

        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        static::assertSame(ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION, $transaction_info['last_status_id']);

        $logs = $this->getLogRecords();
        static::assertMatchesRegularExpression('#Erreur du mock#', $logs[3]['message']);
    }

    private function createTransaction($status, $archive_path)
    {
        $archive_name = basename($archive_path);

        copy($archive_path, $this->enveloppe_directory . "/$archive_name");

        $sql = 'INSERT INTO actes_envelopes(user_id,file_path) VALUES(1,?) returning ID';
        $envelope_id = $this->getSQLQuery()->queryOne($sql, $this->siren . "/$archive_name");


        $sql = 'INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id,antivirus_check) VALUES (?,?,?,?,?) returning ID;';
        $transaction_id = $this->getSQLQuery()->queryOne($sql, $envelope_id, $status, 1, 1, true);

        $sql = 'INSERT INTO actes_transactions_workflow(transaction_id, status_id, date, message, flux_retour) VALUES (?,?,now(),?,?)';
        $this->getSQLQuery()->queryOne($sql, $transaction_id, $status, 'Creation', '');
        return $transaction_id;
    }
}
