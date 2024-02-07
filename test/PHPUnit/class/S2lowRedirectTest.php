<?php

declare(strict_types=1);

namespace PHPUnit\class;

use Exception;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\S2lowRedirect;
use S2lowLegacy\Lib\SessionWrapper;

class S2lowRedirectTest extends TestCase
{
    public function testRedirect()
    {
        $session = array();
        $s2lowRedirect = new S2lowRedirect("https://s2Low/", "http://s2Low/", new SessionWrapper($session));
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("exit() called");
        $this->expectOutputString("header('Location: https://s2Low/toto','1','') called\n");
        $s2lowRedirect->redirect("/toto", "mon message");
        $this->assertEquals("mon messsage", $session[S2lowRedirect::SESSION_MESSAGE_KEY]);
    }
}
