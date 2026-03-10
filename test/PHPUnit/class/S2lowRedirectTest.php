<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\S2lowRedirect;
use S2lowLegacy\Lib\SessionWrapper;

class S2lowRedirectTest extends TestCase
{
    public function testRedirect(): void
    {
        $session = [];
        $s2lowRedirect = new S2lowRedirect('https://s2Low/', 'http://s2Low/', new SessionWrapper($session));
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('exit() called');
        $this->expectOutputString("header('Location: https://s2Low/toto','1','0') called\n");
        $s2lowRedirect->redirect('/toto', 'mon message');
    }
}
