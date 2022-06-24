<?php

require_once __DIR__ . "/../../../../../../public.ssl/modules/actes/class/ActesTransaction.class.php";

class ActesTransactionsTest extends S2lowTestCase
{
    public function testCanValidate()
    {
        $actesTransaction = new ActesTransaction(12);
        $this->assertFalse($actesTransaction->canValidate());
    }
}
