<?php

class ActesResponsesErrorTest extends S2lowTestCase
{
    /**
     * @throws Exception
     */
    public function testDownload()
    {

        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();

        mkdir($tmp_folder . "/test42");

        touch($tmp_folder . "/test42/foo.txt");

        $actesResponsesError = new ActesResponsesError($tmp_folder, $tmpFolder);
        $this->expectOutputRegex("##");
        $actesResponsesError->download("test42");
        $tmpFolder->delete($tmp_folder);
    }
}
