<?php

declare(strict_types=1);

namespace PHPUnit\class;

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\VersionningFactory;

class VersionningTest extends TestCase
{
    public function testVersion()
    {
        $versionning = VersionningFactory::getInstance();
        $info  = $versionning->getAllInfo();
        $this->assertNotEmpty($info['version-complete']);
    }
}
