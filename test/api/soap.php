<?php

ini_set("soap.wsdl_cache_enabled", 0);

$userCert = "admin-certkey.pem";
$userCertPassword = "admin";

$client = new SoapClient("https://192.168.1.5/modules/dia/wsdl.php",
     	array(
     	'login' => 'epommate3',
        'password' => 'winfield', 
     	'local_cert' => $userCert,
		'passphrase' => $userCertPassword,
        'trace'=>1) 

);


$id = 10;
print_r($client->getDIA($id));

print_r($client->setAE($id,"toto.xml","<toto/>"));

$response = $client->__getLastResponse();
$xml = simplexml_load_string($response);

$dom = new DOMDocument('1.0');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xml->asXML());
echo $dom->saveXML();
