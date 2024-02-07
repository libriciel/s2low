<?php

declare(strict_types=1);

namespace PHPUnit\public_ssl\module\actes\class;

use ActesTransaction;
use PHPUnit\S2lowTestCase;

class ActesTransactionsTest extends S2lowTestCase
{
    public function testCanValidate()
    {
        $actesTransaction = new ActesTransaction(12);
        $this->assertFalse($actesTransaction->canValidate());
    }
}
