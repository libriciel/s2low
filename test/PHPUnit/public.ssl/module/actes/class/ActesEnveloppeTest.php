<?php

require_once __DIR__ . "/../../../../../../public.ssl/modules/actes/class/ActesEnvelope.class.php";

class ActesEnveloppeTest extends S2lowTestCase {

    public function testSendFileNotInit(){
        $actesEnvelope = new ActesEnvelope();
        $this->assertFalse($actesEnvelope->sendFile());
    }

    public function testSendFileNotAvailable(){
        $actesEnvelope = new ActesEnvelope();
        $actesEnvelope->set('file_path',"test.txt");
        $this->assertFalse($actesEnvelope->sendFile());
        $this->assertEquals(
            'Le fichier archive n\'est pas/plus disponible.',
            $actesEnvelope->getErrorMsg()
        );
    }

    public function testSendFile(){
        $actesRetriever = $this->getObjectInstancier()->get("ActesRetriever");
        $file_path = $actesRetriever->getPath("test.txt");
        file_put_contents($file_path,"toto");
        $actesEnvelope = new ActesEnvelope();
        $actesEnvelope->set('file_path',"test.txt");
        $this->expectOutputRegex("#toto#");
        $actesEnvelope->sendFile();
    }
}