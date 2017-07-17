<?php

class EnvironnementTest extends PHPUnit_Framework_TestCase {

    public function testAll(){
        $get = array();
        $post = array();
        $request = array();
        $session = array();
        $server = array();
        $environnement = new Environnement($get,$post,$request,$session,$server);
        $this->assertInstanceOf('SessionWrapper',$environnement->session());
        $this->assertInstanceOf('Recuperateur',$environnement->get());
        $this->assertInstanceOf('Recuperateur',$environnement->post());
        $this->assertInstanceOf('Recuperateur',$environnement->request());
        $this->assertInstanceOf('Recuperateur',$environnement->server());
    }
}