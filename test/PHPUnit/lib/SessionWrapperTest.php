<?php

use S2lowLegacy\Lib\SessionWrapper;

class SessionWrapperTest extends PHPUnit_Framework_TestCase
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
