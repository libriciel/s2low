<?php

use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Lib\SessionWrapper;

class EnvironnementTest extends PHPUnit_Framework_TestCase
{
    public function testAll()
    {
        $get = array();
        $post = array();
        $request = array();
        $session = array();
        $server = array();
        $environnement = new Environnement($get, $post, $request, $session, $server);
        $this->assertInstanceOf(SessionWrapper::class, $environnement->session());
        $this->assertInstanceOf(Recuperateur::class, $environnement->get());
        $this->assertInstanceOf(Recuperateur::class, $environnement->post());
        $this->assertInstanceOf(Recuperateur::class, $environnement->request());
        $this->assertInstanceOf(Recuperateur::class, $environnement->server());
    }
}
