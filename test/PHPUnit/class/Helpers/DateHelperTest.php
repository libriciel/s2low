<?php

namespace PHPUnit\class\Helpers;

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\Helpers\DateHelper;

class DateHelperTest extends TestCase
{
    public function testAnsiDateToTimestamp()
    {
        $date = "2023-10-25";
        $this->assertEquals(LegacyHelpers::ansiDateToTimestamp($date), DateHelper::ansiDateToTimestamp($date));
    }

    public function testTimestampToString()
    {
        $time = time();
        $this->assertEquals(LegacyHelpers::TimestampToString($time), DateHelper::TimestampToString($time));
    }

    public function testGetPrettyHours()
    {
        $hour = "12:30:45";
        $this->assertEquals(LegacyHelpers::getPrettyHours($hour), DateHelper::getPrettyHours($hour));
    }

    public function testDatesFromBDD()
    {
        $bddDate = "2023-10-25 12:30:45+0200";
        $this->assertEquals(LegacyHelpers::getTimestampFromBDDDate($bddDate), DateHelper::getTimestampFromBDDDate($bddDate));
        $this->assertEquals(LegacyHelpers::getDateFromBDDDate($bddDate), DateHelper::getDateFromBDDDate($bddDate));
        $this->assertEquals(LegacyHelpers::getANSIDateFromBDDDate($bddDate), DateHelper::getANSIDateFromBDDDate($bddDate));
    }
}
