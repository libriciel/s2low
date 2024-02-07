<?php

declare(strict_types=1);

namespace PHPUnit\lib;

use Exception;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Lib\JSONoutput;

class JSONoutputTest extends TestCase
{
    /**
     * @var JSONoutput
     */
    private $jsonOutput;

    public function setUp(): void
    {
        parent::setUp();
        $this->jsonOutput = new JSONoutput();
    }

    public function testDisplay()
    {
        $this->expectOutputRegex("#\[\]#");
        $this->jsonOutput->display(array());
    }

    public function testDisplayErrorAndExit()
    {
        $this->expectOutputRegex('#\{"status":"error","error-message":"foo"\}#');
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Exit !");
        $this->jsonOutput->displayErrorAndExit("foo");
    }

    public function testRestrictAndDisplay()
    {
        $data = array(array('foo' => 'bar','fii' => 'baz'));
        $this->expectOutputRegex('#\[\{"foo":"bar"\}\]#');
        $this->jsonOutput->retrictAndDisplay($data, array('foo'));
    }
}
