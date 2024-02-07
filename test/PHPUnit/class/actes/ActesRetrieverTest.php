<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use Exception;
use PHPUnit\S2lowTestCase;
use S2lowLegacy\Class\actes\ActesRetriever;
use S2lowLegacy\Class\TmpFolder;

class ActesRetrieverTest extends S2lowTestCase
{
    /**
     * @throws Exception
     */
    public function testGetPath()
    {
        $tmpFolder = new TmpFolder();
        $my_tmp_folder = $tmpFolder->create();
        $this->getObjectInstancier()->set('actes_files_upload_root', $my_tmp_folder);

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
