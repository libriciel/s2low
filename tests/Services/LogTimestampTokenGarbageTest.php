<?php

namespace S2low\Tests\Services;

use Exception;
use LogsHistoriqueSQL;
use S2low\Services\LogTimestampTokenGarbage;
use S2low\Tests\LogsHistoriqueSQLTrait;
use S2lowTestCase;
use TmpFolder;

class LogTimestampTokenGarbageTest extends S2lowTestCase
{
    use LogsHistoriqueSQLTrait;

    public function setUp(): void
    {
        parent::setUp();
        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();
        $this->getObjectInstancier()->set('old_timestamp_token_directory',$tmp_folder);
        $this->getObjectInstancier()->set('timestamp_token_retention_nb_days',10);
        $this->addFixtures();
    }

    public function tearDown(): void
    {
        $tmpFolder = new TmpFolder();
        $tmpFolder->delete($this->getObjectInstancier()->get('old_timestamp_token_directory'));
        parent::tearDown();
    }

    /**
     * @throws Exception
     */
    public function testExtractAndDelete()
    {
        $logTimestampTokenGarbage = $this->getObjectInstancier()->get(LogTimestampTokenGarbage::class);
        $logsHistoriqueSQL = $this->getObjectInstancier()->get(LogsHistoriqueSQL::class);
        $tmp_folder = $logTimestampTokenGarbage->getOldTimestampTokenDirectory();

        $logTimestampTokenGarbage->extractAndDelete(1);

        $destination_file = $tmp_folder."/1977/02/01/".self::$LOG_ID_TO_DELETE.".pem";
        $this->assertFileExists($destination_file);
        $this->assertEquals('baz',file_get_contents($destination_file));
        $info = $logsHistoriqueSQL->getInfo(self::$LOG_ID_TO_DELETE);
        $this->assertEquals("bar",$info['message']);
        $this->assertEquals("",$info['timestamp']);
        $info = $logsHistoriqueSQL->getInfo(self::$LOG_ID_NOT_DELETE_LIMIT);
        $this->assertEquals('baz',$info['timestamp']);
        $info = $logsHistoriqueSQL->getInfo(self::$LOG_ID_NOT_DELETE_DATE);
        $this->assertEquals('baz',$info['timestamp']);
    }

    public function testInfo()
    {
        $logTimestampTokenGarbage = $this->getObjectInstancier()->get(LogTimestampTokenGarbage::class);
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
        ),$info);
    }

    public function getLogHistoriqueSQL(): LogsHistoriqueSQL
    {
        return $this->getObjectInstancier()->get(LogsHistoriqueSQL::class);
    }
}
