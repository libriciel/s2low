<?php

use S2lowLegacy\Class\actes\ActesRetriever;
use S2lowLegacy\Class\ActesWorkspaceForTests;
use S2lowLegacy\Class\IActesWorkspace;

class ActesEnveloppeTest extends S2lowTestCase
{
    private ActesWorkspaceForTests $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workspace = new ActesWorkspaceForTests();
        $this->getObjectInstancier()->set(IActesWorkspace::class, $this->workspace);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->workspace->clear();
    }

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
        $my_tmp_folder = $this->getActesWorkspace()->getFilesUploadRoot();
        file_put_contents("$my_tmp_folder/test.txt", "foo");
        /** @var ActesRetriever $actesRetriever */
        $actesRetriever = $this->getObjectInstancier()->get(ActesRetriever::class);
        $file_path = $actesRetriever->getPath("test.txt");
        file_put_contents($file_path, "toto");
        $actesEnvelope = new ActesEnvelope();
        $actesEnvelope->set('file_path', "test.txt");
        $this->expectOutputRegex("#toto#");
        $actesEnvelope->sendFile();
    }
}
