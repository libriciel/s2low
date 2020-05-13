<?php
require_once( __DIR__."/../../../init/init.php");

use Monolog\Logger;

//include (__DIR__.'/../../../lib/OpenStackConfig.class.php');
//include (__DIR__.'/../../../lib/OpenStackContainerWrapperFactory.class.php');
//include (__DIR__.'/../../../lib/OpenStackContainerStore.class.php');
//include (__DIR__.'/../../../lib/OpenStackSwiftWrapper.class.php');

$openStackConfigActes = new OpenStackConfig();
$openStackConfigActes->openstack_authentication_url_v3  = "pouet";//ACTES_OPENSTACK_AUTHENTICATION_URL_V3;
$openStackConfigActes->openstack_username = ACTES_OPENSTACK_USERNAME;
$openStackConfigActes->openstack_password = ACTES_OPENSTACK_PASSWORD;
$openStackConfigActes->openstack_tenant = ACTES_OPENSTACK_TENANT;
$openStackConfigActes->openstack_region = ACTES_OPENSTACK_REGION;
$openStackConfigActes->openstack_swift_container_prefix = "s2low_dev_jlegall_";

$openStackContainerWrapperFactory = new OpenStackContainerWrapperFactory();
$openStackContainerStore = new OpenStackContainerStore($openStackContainerWrapperFactory);

$openStackContainerStore->addConfiguration('temp',$openStackConfigActes);
$logger = new Logger("TEST");

$wrapper = new OpenStackSwiftWrapper($openStackContainerStore,$logger);

while(true){
    try{
        $wrapper->sendFile('temp',
            'TestContainer.php',
            "test");
        $wrapper->deleteFile('temp',"test");
    }
    catch(Throwable $e){
        echo "-----------------------------------------------------------------------\n";
        var_dump($e->getMessage());
        echo get_class($e);
        die();
        echo "-----------------------------------------------------------------------\n";
        var_dump($e->getMessage());
    }
}

