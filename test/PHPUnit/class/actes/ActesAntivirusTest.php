<?php

use S2lowLegacy\Class\actes\ActesAntivirusWorker;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\Antivirus;
use S2lowLegacy\Class\TmpFolder;

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
    }

    protected function tearDown(): void
    {
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
        $this->getObjectInstancier()->set(Antivirus::class, $antivirus);
        $actesAntivirus = $this->getObjectInstancier()->get(ActesAntivirusWorker::class);
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

        $this->getObjectInstancier()->set(Antivirus::class, $antivirus);

        $actesAntivirus = $this->getObjectInstancier()->get(ActesAntivirusWorker::class);
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

        $this->getObjectInstancier()->set(Antivirus::class, $antivirus);

        $actesAntivirus = $this->getObjectInstancier()->get(ActesAntivirusWorker::class);
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
        $actesAntivirus = $this->getObjectInstancier()->get(ActesAntivirusWorker::class);
        $actesAntivirus->work($this->transaction_id);
        $logs = $this->getLogRecords();
        $this->assertEquals("La transaction {$this->transaction_id} a déjà été analysé par l'antivirus", $logs[1]['message']);
    }

    public function testGetAll()
    {
        $actesAntivirus = $this->getObjectInstancier()->get(ActesAntivirusWorker::class);
        $all_id = $actesAntivirus->getAllId();
        $this->assertEquals([$this->transaction_id], $all_id);
    }

    public function testGetId()
    {
        $actesAntivirus = $this->getObjectInstancier()->get(ActesAntivirusWorker::class);
        $this->assertEquals($this->transaction_id, $actesAntivirus->getData($this->transaction_id));
    }

    public function testGetQueueId()
    {
        $actesAntivirus = $this->getObjectInstancier()->get(ActesAntivirusWorker::class);
        $this->assertEquals(ActesAntivirusWorker::QUEUE_NAME, $actesAntivirus->getQueueName());
    }
}
