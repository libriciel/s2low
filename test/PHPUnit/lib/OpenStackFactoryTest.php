<?php

class OpenStackFactoryTest extends PHPUnit_Framework_TestCase {

    public function testGetInstance(){

    	$openStackConfig = new OpenStackConfig();

    	$openStackConfig->openstack_authentication_url_v2 = "a";
		$openStackConfig->openstack_username = "b";
		$openStackConfig->openstack_password = "c";
		$openStackConfig->openstack_tenant = "d";

        $openStackFactory = new OpenStackFactory($openStackConfig);

        $openStack = $openStackFactory->getInstance();
        $this->assertInstanceOf("\OpenCloud\OpenStack",$openStack);
        $this->assertEquals("a",$openStack->getAuthUrl());
    }



}