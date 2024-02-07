<?php

declare(strict_types=1);

namespace PHPUnit\public_ssl\module\actes\class;

use ActesBatch;
use Exception;
use PHPUnit\S2lowTestCase;

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
