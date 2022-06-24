<?php

class S2lowLoggerTest extends S2lowTestCase
{
    public function testAll()
    {
        $s2lowLogger = $this->getObjectInstancier()->get(S2lowLogger::class);

        $message_type_list = ['debug','info','notice','warning','error','alert','critical','emergency'];

        foreach ($message_type_list as $type) {
            $s2lowLogger->$type("test-$type");
        }

        $records = $this->getLogRecords();
        foreach ($message_type_list as $i => $type) {
            $this->assertEquals($records[$i]['message'], "test-$type");
            $this->assertEquals($records[$i]['level_name'], mb_strtoupper($type));
        }
    }
}
