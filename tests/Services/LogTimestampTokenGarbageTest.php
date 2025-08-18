<?php

namespace S2low\Tests\Services;

use Exception;
use Psr\Log\LoggerInterface;
use S2lowLegacy\Model\LogsHistoriqueSQL;
use S2low\Services\LogTimestampTokenGarbage;
use S2low\Tests\LogsHistoriqueSQLTrait;
use S2lowTestCase;
use S2lowLegacy\Class\TmpFolder;

class LogTimestampTokenGarbageTest extends S2lowTestCase
{
    use LogsHistoriqueSQLTrait;

    private $old_timestamp_token_directory;
    private int $timestamp_token_retention_nb_days = 10;

    public function setUp(): void
    {
        parent::setUp();
        $this->old_timestamp_token_directory =  (new TmpFolder())->create();
        $this->addFixtures();
    }

    public function tearDown(): void
    {
        (new TmpFolder())->delete($this->old_timestamp_token_directory);
        parent::tearDown();
    }

    /**
     * @throws Exception
     */
    public function testExtractAndDelete()
    {
        $logsHistoriqueSQL = $this->getObjectInstancier()->get(LogsHistoriqueSQL::class);

        $logTimestampTokenGarbage = new LogTimestampTokenGarbage(
            $this->old_timestamp_token_directory,
            $this->timestamp_token_retention_nb_days,
            $logsHistoriqueSQL,
            $this->getObjectInstancier()->get(LoggerInterface::class)
        );

        $tmp_folder = $logTimestampTokenGarbage->getOldTimestampTokenDirectory();

        $logTimestampTokenGarbage->extractAndDelete(1);

        $destination_file = $tmp_folder . "/1977/02/01/" . self::$LOG_ID_TO_DELETE . ".pem";
        $this->assertFileExists($destination_file);
        $this->assertEquals('baz', file_get_contents($destination_file));
        $info = $logsHistoriqueSQL->getInfo(self::$LOG_ID_TO_DELETE);
        $this->assertEquals("bar", $info['message']);
        $this->assertEquals("", $info['timestamp']);
        $info = $logsHistoriqueSQL->getInfo(self::$LOG_ID_NOT_DELETE_LIMIT);
        $this->assertEquals('baz', $info['timestamp']);
        $info = $logsHistoriqueSQL->getInfo(self::$LOG_ID_NOT_DELETE_DATE);
        $this->assertEquals('baz', $info['timestamp']);
    }

    public function testInfo()
    {
        $logsHistoriqueSQL = $this->getObjectInstancier()->get(LogsHistoriqueSQL::class);

        $logTimestampTokenGarbage = new LogTimestampTokenGarbage(
            $this->old_timestamp_token_directory,
            $this->timestamp_token_retention_nb_days,
            $logsHistoriqueSQL,
            $this->getObjectInstancier()->get(LoggerInterface::class)
        );

        $info = $logTimestampTokenGarbage->getInfo(1);
        $this->assertEquals(array (
            'older-than' => 10,
            'limit' => 1,
            'nb_result' => 1,
            'min_date' =>
                array (
                    'id' => 42,
                    'date' => '1977-02-01 00:00:00+01',
                    'timestamp' => 'baz',
                ),
            'max_date' =>
                array (
                    'id' => 42,
                    'date' => '1977-02-01 00:00:00+01',
                    'timestamp' => 'baz',
                ),
        ), $info);
    }

    public function getLogHistoriqueSQL(): LogsHistoriqueSQL
    {
        return self::getContainer()->get(LogsHistoriqueSQL::class);
    }
}
