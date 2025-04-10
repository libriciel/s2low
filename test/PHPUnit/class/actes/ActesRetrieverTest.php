<?php

use S2lowLegacy\Class\actes\ActesRetriever;
use S2lowLegacy\Class\IActesWorkspace;
use S2lowLegacy\Class\TmpFolder;

class ActesRetrieverTest extends S2lowTestCase
{
    /**
     * @throws Exception
     */
    public function testGetPath()
    {
        $tmpFolder = new TmpFolder();
        $my_tmp_folder = $this->getActesWorkspace()->getFilesUploadRoot();

        mkdir($my_tmp_folder . "/foo");
        file_put_contents("$my_tmp_folder/foo/bar", "foo");
        $actesRetriever = $this->getObjectInstancier()->get(ActesRetriever::class);

        $this->assertEquals(
            "$my_tmp_folder/foo/bar",
            $actesRetriever->getPath("foo/bar")
        );
        $tmpFolder->delete($my_tmp_folder);
    }
}
