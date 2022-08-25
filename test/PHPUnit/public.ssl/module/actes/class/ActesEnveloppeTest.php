<?php

use S2lowLegacy\Class\actes\ActesRetriever;
use S2lowLegacy\Class\TmpFolder;

class ActesEnveloppeTest extends S2lowTestCase
{
    public function testSendFileNotInit()
    {
        $actesEnvelope = new ActesEnvelope();
        $this->assertFalse($actesEnvelope->sendFile());
    }

    public function testSendFileNotAvailable()
    {
        $actesEnvelope = new ActesEnvelope();
        $actesEnvelope->set('file_path', "test.txt");
        $this->assertFalse($actesEnvelope->sendFile());
        $this->assertEquals(
            'Le fichier archive n\'est pas/plus disponible.',
            $actesEnvelope->getErrorMsg()
        );
    }

    /**
     * @throws Exception
     */
    public function testSendFile()
    {
        $tmpFolder = new TmpFolder();
        $my_tmp_folder = $tmpFolder->create();
        $this->getObjectInstancier()->set('actes_files_upload_root', $my_tmp_folder);
        file_put_contents("$my_tmp_folder/test.txt", "foo");
        $actesRetriever = $this->getObjectInstancier()->get(ActesRetriever::class);
        $file_path = $actesRetriever->getPath("test.txt");
        file_put_contents($file_path, "toto");
        $actesEnvelope = new ActesEnvelope();
        $actesEnvelope->set('file_path', "test.txt");
        $this->expectOutputRegex("#toto#");
        $actesEnvelope->sendFile();
        $tmpFolder->delete($my_tmp_folder);
    }
}
