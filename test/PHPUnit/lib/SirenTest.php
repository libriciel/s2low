<?php

use S2lowLegacy\Lib\LuhnKey;
use S2lowLegacy\Lib\Siren;
use S2lowLegacy\Lib\SirenFactory;

class SirenTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \S2lowLegacy\Lib\SirenFactory
     */
    private SirenFactory $sirenFactory;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->sirenFactory = new SirenFactory(new LuhnKey());
    }

    public function get_data()
    {
        return [
            ['000000000',true],
            ['000000001',false],
            ['493587273',true],
            [' 4 9   35 8 7   2 73    ',true,'493587273'],
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
    public function testAllSiren($siren_to_test, $expected_result, $expectedValue = null)
    {
        $siren = $this->sirenFactory->get($siren_to_test);
        $this->assertEquals(
            $expected_result,
            $siren->isValid()
        );
        if (is_null($expectedValue)) {
            $expectedValue = $siren_to_test;
        }
        $this->assertEquals(
            $expectedValue,
            $siren->getValue()
        );
    }

    public function testGenerate()
    {
        $siren = $this->sirenFactory->generate();
        $this->assertTrue($siren->isValid());
    }
}
