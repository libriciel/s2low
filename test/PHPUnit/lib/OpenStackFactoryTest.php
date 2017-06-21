<?php

class OpenStackFactoryTest extends PHPUnit_Framework_TestCase {

    public function testGetInstance(){

        $openStackFactory = new OpenStackFactory(
            "a",
            "b",
            "c",
            "d"
            );

        $openStack = $openStackFactory->getInstance();
        $this->assertInstanceOf("\OpenCloud\OpenStack",$openStack);
        $this->assertEquals("a",$openStack->getAuthUrl());
    }

}