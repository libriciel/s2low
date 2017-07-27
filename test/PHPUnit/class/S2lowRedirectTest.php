<?php

class S2lowRedirectTest extends PHPUnit_Framework_TestCase {

    public function testRedirect(){
        $session = array();
        $s2lowRedirect = new S2lowRedirect("https://s2Low/","http://s2Low/",new SessionWrapper($session));
        $this->setExpectedException("Exception","exit() called");
        $this->expectOutputString("header('Location: https://s2Low/toto','1','') called\n");
        $s2lowRedirect->redirect("/toto","mon message");
        $this->assertEquals("mon messsage",$session[S2lowRedirect::SESSION_MESSAGE_KEY]);
    }

}