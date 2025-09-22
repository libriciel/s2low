<?php

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\actes\ActesRetriever;
//use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;

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
        file_put_contents("$my_tmp_folder/test.txt", "foo");
        $actesRetriever = new ActesRetriever(
            $my_tmp_folder,
            self::getContainer()->get(OpenStackSwiftWrapper::class),
            self::getContainer()->get(LoggerInterface::class)
        );
        self::getContainer()->set(ActesRetriever::class, $actesRetriever);
        $file_path = $actesRetriever->getPath("test.txt");
        file_put_contents($file_path, "toto");
        $actesEnvelope = new ActesEnvelope();
        $actesEnvelope->set('file_path', "test.txt");
        $this->expectOutputRegex("#toto#");
        $actesEnvelope->sendFile();
        $tmpFolder->delete($my_tmp_folder);
    }
}
