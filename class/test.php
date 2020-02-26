<?php


use GuzzleHttp\Psr7\Stream;

require_once(__DIR__.'/../vendor/autoload.php');

include(__DIR__."/../lib/OpenStackConfig.class.php");
include(__DIR__."/../lib/OpenStackFactory.class.php");

include(__DIR__."/../lib/OpenStackSwiftWrapper.class.php");


//const CONTAINER_NAME = "pes_aller";
const CONTAINER_NAME = "temp";
const FILENAME = "test.php";

function checkExistence( OpenStackSwiftWrapper $wrapper,$containerName,$fileName){
    if($wrapper->fileExistsOnCloud("" . CONTAINER_NAME . "", FILENAME)){
        echo "\n Le fichier $fileName existe dans $containerName\n";
    }
    else{
        echo "\n Le fichier $fileName n'existe pas dans $containerName\n";
    }
}

$openStackConfig = new OpenStackConfig();
$openStackConfig->openstack_authentication_url_v2  = "https://auth.cloud.ovh.net/v3/";
$openStackConfig->openstack_username = "Z8b5fvy4FZFg";
$openStackConfig->openstack_password = "8XgRbwZM7EUxTGvfUXggGjEUJUg7XsUm";
$openStackConfig->openstack_tenant = "3776916535713556";
$openStackConfig->openstack_region = "GRA";
$openStackConfig->openstack_swift_container_prefix = "s2low_dev_jlegall_";

$openStackFactory = new OpenStackFactory();
$openStackFactory->addConfiguration(CONTAINER_NAME,$openStackConfig);
$logger = new Monolog\Logger("truc");

$wrapper = new OpenStackSwiftWrapper($openStackFactory,$logger);


#------------------------------------------------------------------------------------------------------------------
/*echo "getContainer\n";
try {
    $container = $wrapper->getContainer(CONTAINER_NAME);
}
catch (Exception $e){
    var_dump($e->getMessage());
}*/

checkExistence($wrapper,CONTAINER_NAME,FILENAME);

echo "\nsendFile---------------------------------------\n";
try {
    $wrapper->sendFile(CONTAINER_NAME, FILENAME);
}
catch (Exception $e){
    echo "\nException : \n";
    var_dump($e->getMessage());
}
echo "---------------------------------";

checkExistence($wrapper,CONTAINER_NAME,FILENAME);

echo "\ndeleteFile---------------------------------------\n";
try{
$wrapper->deleteFile(CONTAINER_NAME, FILENAME);
}
catch (Exception $e){
    echo "\nException : \n";
    var_dump($e->getMessage());
}
echo "---------------------------------";

checkExistence($wrapper,CONTAINER_NAME,FILENAME);