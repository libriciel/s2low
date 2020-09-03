<?php

class FTPFileSenderTest extends S2lowTestCase {

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testBasic(){
        $a = $this->getObjectInstancier()->get(FTPHeliosSender::class);
        $this->assertTrue(isset($a));
    }

    public function testBasic2(){
        $a = $this->getObjectInstancier()->get(HeliosEnvoiControler::class);
        $this->assertTrue(isset($a));
    }

    public function testBasic3(){
        $a = $this->getObjectInstancier()->get(FTPService::class);
        $this->assertTrue(isset($a));
    }
}