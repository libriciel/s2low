<?php

class ActesRetrieverTest extends PHPUnit_Framework_TestCase {

    public function testGetPath(){
        $actesRetriever = new ActesRetriever("/foo/");
        $this->assertEquals(
            '/foo/bar/baz',
            $actesRetriever->getPath("bar/baz")
        );
    }

}