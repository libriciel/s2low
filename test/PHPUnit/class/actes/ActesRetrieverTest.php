<?php

use S2lowLegacy\Class\actes\ActesRetriever;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;

class ActesRetrieverTest extends S2lowTestCase
{
    /**
     * @throws Exception
     */
    public function testGetPath()
    {
        mkdir($this->tmpPathFolder . "/foo");
        file_put_contents("$this->tmpPathFolder/foo/bar", "foo");
        $actesRetriever = $this->getActesRetriever();

        $this->assertEquals(
            "$this->tmpPathFolder/foo/bar",
            $actesRetriever->getPath("foo/bar")
        );
    }

    private function getActesRetriever(): ActesRetriever
    {
        return new ActesRetriever(
            $this->tmpPathFolder,
            self::getContainer()->get(OpenStackSwiftWrapper::class),
            $this->s2lowLogger
        );
    }
}
