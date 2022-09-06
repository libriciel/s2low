<?php

use S2lowLegacy\Class\VersionningFactory;

class VersionningTest extends PHPUnit_Framework_TestCase
{
    public function testVersion()
    {
        $versionning = VersionningFactory::getInstance();
        $info  = $versionning->getAllInfo();
        $this->assertNotEmpty($info['version-complete']);
    }
}
