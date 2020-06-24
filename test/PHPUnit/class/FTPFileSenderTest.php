<?php

class FTPFileSenderTest extends S2lowTestCase {

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testBasic(){
        $a = $this->getObjectInstancier()->get(FTPFileSender::class);
        $this->assertTrue(isset($a));
    }

    public function testBasic2(){
        $a = $this->getObjectInstancier()->get(HeliosEnvoiControler::class);
        $this->assertTrue(isset($a));
    }

    public function testBasic3(){
        $a = $this->getObjectInstancier()->get(FTPConnection::class);
        $this->assertTrue(isset($a));
    }
}