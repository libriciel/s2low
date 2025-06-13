<?php

use Monolog\Level;
use PHPUnit\ActesUtilitiesTestTrait;
use S2lowLegacy\Class\actes\ActesAntivirusWorker;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesRetriever;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\Antivirus;
use S2lowLegacy\Class\WorkerScript;

require_once __DIR__ . "/ActesCreator.php";

class ActesAntivirusTest extends S2lowTestCase
{
    use ActesUtilitiesTestTrait;

    private $transaction_id;

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
        self::getContainer()->set(Antivirus::class, $antivirus);
        $actesAntivirus = self::getContainer()->get(ActesAntivirusWorker::class);
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

        self::getContainer()->set(Antivirus::class, $antivirus);

        $actesAntivirus = $this->getActesAntivirusWorker();
        $this->assertFalse($actesAntivirus->work($this->transaction_id));
    }

    private function getActesAntivirusWorker(): ActesAntivirusWorker
    {
        $s2lowLogger = $this->s2lowLogger;

        return new ActesAntivirusWorker(
            $this->getActesTransactionsSQL(),
            self::getContainer()->get(ActesRetriever::class),
            self::getContainer()->get(ActesEnvelopeSQL::class),
            self::getContainer()->get(Antivirus::class),
            $s2lowLogger,
            self::getContainer()->get(WorkerScript::class)
        );
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return self::getContainer()->get(ActesTransactionsSQL::class);
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

        self::getContainer()->set(Antivirus::class, $antivirus);

        $actesAntivirus = $this->getActesAntivirusWorker();
        $this->expectExceptionMessage("testing");
        $actesAntivirus->work($this->transaction_id);
    }

    /**
     * @throws Exception
     */
    public function testAlreadyAnalysed()
    {
        $actesTransactionSQL = self::getActesTransactionsSQL();
        $actesTransactionSQL->setAntivirusCheck($this->transaction_id);
        $actesAntivirus = $this->getActesAntivirusWorker();
        $actesAntivirus->work($this->transaction_id);

        self::assertTrue(
            $this->testHandler->hasRecordThatContains(
                "La transaction $this->transaction_id a déjà été analysé par l'antivirus",
                Level::Notice
            )
        );
    }

    public function testGetAll()
    {
        $actesAntivirus = $this->getActesAntivirusWorker();
        $all_id = $actesAntivirus->getAllId();
        $this->assertEquals([$this->transaction_id], $all_id);
    }

    public function testGetId()
    {
        $actesAntivirus = $this->getActesAntivirusWorker();
        $this->assertEquals($this->transaction_id, $actesAntivirus->getData($this->transaction_id));
    }

    public function testGetQueueId()
    {
        $actesAntivirus = $this->getActesAntivirusWorker();
        $this->assertEquals(ActesAntivirusWorker::QUEUE_NAME, $actesAntivirus->getQueueName());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->transaction_id = $this->createTransactionWithStatus(
            ActesStatusSQL::STATUS_POSTE,
            __DIR__ . "/fixtures/abc-TACT--000000000--20170803-16.tar.gz",
        );
    }
}
