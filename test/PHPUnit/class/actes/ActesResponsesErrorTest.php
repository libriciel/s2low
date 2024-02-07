<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use Exception;
use PHPUnit\S2lowTestCase;
use S2lowLegacy\Class\actes\ActesResponsesError;
use S2lowLegacy\Class\TmpFolder;

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
