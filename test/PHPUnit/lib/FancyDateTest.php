<?php

use S2lowLegacy\Lib\FancyDate;

class FancyDateTest extends S2lowTestCase
{
    public function testGetDateFrancais()
    {
        $fancyDate = new FancyDate();
        $date_fr = $fancyDate->getDateFrancais("1977-02-18");
        $this->assertEquals("18 février 1977", $date_fr);
    }
}
