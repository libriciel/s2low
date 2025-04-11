<?php

use S2lowLegacy\Class\actes\ActesRetriever;
use S2lowLegacy\Class\ActesWorkspace;
use S2lowLegacy\Class\TmpFolder;

class ActesRetrieverTest extends S2lowTestCase
{
    private ActesWorkspace $workspace;
    protected function setUp(): void
    {
        parent::setUp();
        $this->workspace = $this->actesWorkspaceManager->get();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->actesWorkspaceManager->delete($this->workspace);
    }

    /**
     * @throws Exception
     */
    public function testGetPath()
    {
        $tmpFolder = new TmpFolder();
        $my_tmp_folder = $this->workspace->getFilesUploadRoot();

        mkdir($my_tmp_folder . "/foo");
        file_put_contents("$my_tmp_folder/foo/bar", "foo");
        $actesRetriever = new ActesRetriever(
            $this->getObjectInstancier()->get(\S2lowLegacy\Lib\OpenStackSwiftWrapper::class),
            $this->getObjectInstancier()->get(\S2lowLegacy\Class\S2lowLogger::class),
            $this->workspace
        );

        $this->assertEquals(
            "$my_tmp_folder/foo/bar",
            $actesRetriever->getPath("foo/bar")
        );
        $tmpFolder->delete($my_tmp_folder);
    }
}
