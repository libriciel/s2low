<?php

use S2lowLegacy\Lib\LuhnKey;
use S2lowLegacy\Lib\Siren;

class SirenTest extends PHPUnit_Framework_TestCase
{
    public function get_data()
    {
        return [
            ['000000000',true],
            ['000000001',false],
            ['493587273',true],
            ['',false],
            ['493587274',false],
            ['MIG_Blign',false],
            [493587273,true],
            [493587274,false],
        ];
    }

    /**
     * @dataProvider get_data
     */
    public function testAllSiren($siren_to_test, $expected_result)
    {
        $siren = new Siren(new LuhnKey());
        $this->assertEquals(
            $expected_result,
            $siren->isValid($siren_to_test)
        );
    }

    public function testGenerate()
    {
        $siren = new Siren(new LuhnKey());
        $this->assertTrue($siren->isValid($siren->generate()));
    }
}
