<?php

declare(strict_types=1);

namespace PHPUnit\public_ssl\module\actes\class;

use ActesBatchFile;
use Exception;
use PHPUnit\S2lowTestCase;

class ActesBatchFileTest extends S2lowTestCase
{
    /**
     * @throws Exception
     */
    public function testInit()
    {
        $actesBatchFile = new ActesBatchFile(1);
        $this->assertFalse($actesBatchFile->init());
    }
}
