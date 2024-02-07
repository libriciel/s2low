<?php

declare(strict_types=1);

namespace PHPUnit\lib;

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Lib\SessionWrapper;

class SessionWrapperTest extends TestCase
{
    public function testSetGet()
    {
        $session = array();
        $sessionWrapper = new SessionWrapper($session);
        $sessionWrapper->set('foo', 'bar');
        $this->assertEquals('bar', $sessionWrapper->get('foo'));
        $this->assertEquals('bar', $session['foo']);
    }

    public function testGetWithDefault()
    {
        $session = array();
        $sessionWrapper = new SessionWrapper($session);
        $sessionWrapper->set('foo', 'bar');
        $this->assertEquals('42', $sessionWrapper->get('baz', '42'));
    }
}
