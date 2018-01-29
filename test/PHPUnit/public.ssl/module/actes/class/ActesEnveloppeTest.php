<?php

require_once __DIR__ . "/../../../../../../public.ssl/modules/actes/class/ActesEnvelope.class.php";

class ActesEnveloppeTest extends PHPUnit_Framework_TestCase {

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
        echo ACTES_FILES_UPLOAD_ROOT;
    }

    public function testSendFile(){
        file_put_contents(ACTES_FILES_UPLOAD_ROOT."/test.txt","toto");
        $actesEnvelope = new ActesEnvelope();
        $actesEnvelope->set('file_path',"test.txt");
        $this->expectOutputRegex("#toto#");
        $actesEnvelope->sendFile();
    }


}