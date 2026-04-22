<?php

namespace S2lowLegacy\Test\Class\Helpers;

use PHPUnit\class\Helpers\LegacyHelpers;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\Helpers\RequestHelper;

class RequestHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_POST = ['test_post' => 'value'];
        $_GET = ['test_get' => 'value2', 'api' => '1'];
        $_FILES = ['test_files' => ['name' => 'test.txt']];
        $_SESSION = [];
    }

    public function testGetVarFromPost()
    {
        $this->assertEquals(LegacyHelpers::getVarFromPost('test_post'), RequestHelper::getVarFromPost('test_post'));
    }

    public function testGetVarFromGet()
    {
        $this->assertEquals(LegacyHelpers::getVarFromGet('test_get'), RequestHelper::getVarFromGet('test_get'));
    }

    public function testGetFiles()
    {
        $this->assertEquals(LegacyHelpers::getFiles('test_files'), RequestHelper::getFiles('test_files'));
    }

    public function testGetInt()
    {
        $_POST['int_val'] = '123';
        $this->assertEquals(LegacyHelpers::getIntFromPost('int_val', true), RequestHelper::getIntFromPost('int_val', true));
    }
}
