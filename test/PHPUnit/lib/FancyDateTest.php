<?php

declare(strict_types=1);

namespace PHPUnit\lib;

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Lib\FancyDate;

class FancyDateTest extends TestCase
{
    public function testGetDateFrancais()
    {
        $fancyDate = new FancyDate();
        $date_fr = $fancyDate->getDateFrancais("1977-02-18");
        $this->assertEquals("18 février 1977", $date_fr);
    }
}
