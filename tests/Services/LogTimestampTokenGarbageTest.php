<?php

namespace S2low\Tests\Services;

use LogsSQL;
use S2low\Services\LogTimestampTokenGarbage;
use S2lowTestCase;
use TmpFolder;

class LogTimestampTokenGarbageTest extends S2lowTestCase
{

    public function testExtractAndDelete()
    {
        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();

        $this->getObjectInstancier()->set('old_timestamp_token_directory',$tmp_folder);
        $this->getObjectInstancier()->set('timestamp_token_retention_nb_days',10);
        $logTimestampTokenGarbage = $this->getObjectInstancier()->get(LogTimestampTokenGarbage::class);

        $logsSQL = $this->getObjectInstancier()->get(LogsSQL::class);
        $log_id = $logsSQL->addLog(
            '1977-02-01',
            LogsSQL::LEVEL_CRITICAL,
            "actes",
            "TdT",
            "1",
            "SADM",
            "bar",
            "baz"
        );

        $logTimestampTokenGarbage->extractAndDelete(1);
        $destination_file = $tmp_folder."/1977/02/01/$log_id.pem";
        $this->assertFileExists($destination_file);
        $this->assertEquals('baz',file_get_contents($destination_file));
        $info = $logsSQL->getInfo($log_id);
        $this->assertEquals("bar",$info['message']);
        $this->assertEquals("",$info['timestamp']);
        $tmpFolder->delete($tmp_folder);
    }

}