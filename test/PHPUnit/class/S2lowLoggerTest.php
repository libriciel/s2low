<?php

use Monolog\Level;

class S2lowLoggerTest extends S2lowTestCase
{
    public function testAll()
    {

        $message_type_list = ['debug','info','notice','warning','error','alert','critical','emergency'];

        foreach ($message_type_list as $type) {
            $this->s2lowLogger->$type("test-$type");
        }

        foreach ($message_type_list as $i => $type) {
            $this->assertTrue(
                $this->testHandler->hasRecord(
                    "test-$type",
                    Level::fromName($type)
                )
            );
        }
    }
}
