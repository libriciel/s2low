<?php

use S2lowLegacy\Class\actes\ActesAntivirusWorker;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesRetriever;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\ActesWorkspaceForTests;
use S2lowLegacy\Class\Antivirus;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;

require_once __DIR__ . "/ActesCreator.php";

class ActesAntivirusTest extends S2lowTestCase
{
    /** @var  TmpFolder */
    private $tmpFolder;
    private $tmp_dir;

    private $transaction_id;
    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpFolder = new TmpFolder();
        $this->tmp_dir = $this->tmpFolder->create();
        $actesCreator = $this->getObjectInstancier()->get(ActesCreator::class);
        $this->transaction_id = $actesCreator->createTransaction(
            ActesStatusSQL::STATUS_POSTE,
            __DIR__ . "/fixtures/abc-TACT--000000000--20170803-16.tar.gz",
            $this->tmp_dir
        );
        $this->workspace = new ActesWorkspaceForTests();

        $this->actesRetriever = new ActesRetriever(
            $this->getObjectInstancier()->get(OpenStackSwiftWrapper::class),
            $this->getObjectInstancier()->get(S2lowLogger::class),
            $this->workspace
        );
    }

    protected function tearDown(): void
    {
        $this->workspace->clear();
        $this->tmpFolder->delete($this->tmp_dir);
        parent::tearDown();
    }

    /**
     * @throws Exception
     */
    public function testOK()
    {
        $antivirus = $this->getMockBuilder(Antivirus::class)
            ->disableOriginalConstructor()
            ->getMock();
        $antivirus
            ->method("checkArchiveSanity")
            ->willReturn(true);

        $actesAntivirus = $this->getAntivirusWorker($antivirus);
        $this->assertTrue($actesAntivirus->work($this->transaction_id));
    }

    /**
     * @throws Exception
     */
    public function testFailed()
    {
        $antivirus = $this->getMockBuilder(Antivirus::class)
            ->disableOriginalConstructor()
            ->getMock();
        $antivirus
            ->method("checkArchiveSanity")
            ->willReturn(false);

        $actesAntivirus = $this->getAntivirusWorker($antivirus);
        $this->assertFalse($actesAntivirus->work($this->transaction_id));
    }

    /**
     * @throws Exception
     */
    public function testRaiseException()
    {
        $antivirus = $this->getMockBuilder(Antivirus::class)
            ->disableOriginalConstructor()
            ->getMock();
        $antivirus
            ->method("checkArchiveSanity")
            ->willThrowException(new Exception("testing"));

        $actesAntivirus = $this->getAntivirusWorker($antivirus);
        $this->setExpectedException(Exception::class, "testing");
        $actesAntivirus->work($this->transaction_id);
    }

    /**
     * @throws Exception
     */
    public function testAlreadyAnalysed()
    {
        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $actesTransactionSQL->setAntivirusCheck($this->transaction_id);
        $actesAntivirus = $this->getAntivirusWorker();
        $actesAntivirus->work($this->transaction_id);
        $logs = $this->getLogRecords();
        $this->assertEquals("La transaction {$this->transaction_id} a déjà été analysé par l'antivirus", $logs[1]['message']);
    }

    public function testGetAll()
    {
        $actesAntivirus = $this->getAntivirusWorker();
        $all_id = $actesAntivirus->getAllId();
        $this->assertEquals([$this->transaction_id], $all_id);
    }

    public function testGetId()
    {
        $actesAntivirus = $this->getAntivirusWorker();
        $this->assertEquals($this->transaction_id, $actesAntivirus->getData($this->transaction_id));
    }

    public function testGetQueueId()
    {
        $actesAntivirus = $this->getAntivirusWorker();
        $this->assertEquals(ActesAntivirusWorker::QUEUE_NAME, $actesAntivirus->getQueueName());
    }

    /**
     * @return mixed
     */
    private function getAntivirusWorker(?Antivirus $antivirus = null): ActesAntivirusWorker
    {
        if (is_null($antivirus)) {
            $antivirus = $this->getObjectInstancier()->get(Antivirus::class);
        }
        return new ActesAntivirusWorker(
            $this->getObjectInstancier()->get(ActesTransactionsSQL::class),
            $this->actesRetriever,
            $this->getObjectInstancier()->get(ActesEnvelopeSQL::class),
            $antivirus,
            $this->getObjectInstancier()->get(S2lowLogger::class),
            $this->getObjectInstancier()->get(WorkerScript::class)
        );
    }
}
