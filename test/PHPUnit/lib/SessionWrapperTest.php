<?php

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Lib\SessionWrapper;

class SessionWrapperTest extends TestCase
{
    public function testSetGet()
    {
        $session = [];
        $sessionWrapper = new SessionWrapper($session);
        $sessionWrapper->set('foo', 'bar');
        $this->assertEquals('bar', $sessionWrapper->get('foo'));
        $this->assertEquals('bar', $session['foo']);
    }

    public function testGetWithDefault()
    {
        $session = [];
        $sessionWrapper = new SessionWrapper($session);
        $sessionWrapper->set('foo', 'bar');
        $this->assertEquals('42', $sessionWrapper->get('baz', '42'));
    }

    public function testGet()
    {
        $session['test'] = 'ceci est une valeur de test';

        $sessionWrapper = new SessionWrapper($session);
        $this->assertEquals('ceci est une valeur de test', $sessionWrapper->get('test'));
    }

    public function testGetWithSessionEmpty()
    {
        $session = [];

        $sessionWrapper = new SessionWrapper($session);
        $this->assertFalse($sessionWrapper->get('test'));
    }
}
