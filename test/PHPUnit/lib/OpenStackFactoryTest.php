<?php

class OpenStackFactoryTest extends PHPUnit_Framework_TestCase {

	/**
	 * @throws UnrecoverableException
	 */
    public function testGetInstance(){

    	$openStackConfig = new OpenStackConfig();

    	$openStackConfig->openstack_authentication_url_v2 = "a";
		$openStackConfig->openstack_username = "b";
		$openStackConfig->openstack_password = "c";
		$openStackConfig->openstack_tenant = "d";

        $openStackFactory = new OpenStackFactory();
        $openStackFactory->addConfiguration("actes",$openStackConfig);

        $openStack = $openStackFactory->getInstance("actes");

        //Hack sale pour récupérer la propriété privée authUrl
        //réalisé pour préserver la couverture de test lors du passage de  rackspace/php-opencloud vers
        // php-opencloud/openstack
        $openStackOpenStackbuilder = ((array)$openStack)["\000OpenStack\OpenStack\000builder"];
        $openStackAuthUrl = ((array)$openStackOpenStackbuilder)["\000OpenStack\Common\Service\Builder\000globalOptions"]["authUrl"];
        $this->assertInstanceOf("\OpenStack\OpenStack",$openStack);
        $this->assertEquals("a",$openStackAuthUrl);
    }



}