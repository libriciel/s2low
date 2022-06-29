<?php

class ActesBatchTest extends S2lowTestCase
{
    /**
     * @throws Exception
     */
    public function testInit()
    {
        $actesBatch = new ActesBatch(1);
        $this->assertFalse($actesBatch->init());
    }
}
