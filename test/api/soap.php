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



//print_r($client->version());

print_r($client->getDIA(17));
/*try {
print_r($client->getDia(12));
} catch (Exception $e){
	echo $e->getMessage();
}*/

/*$id = 13;
print_r($client->setAE($id,"titi.xml","<titi/>"));*/

//print_r($client->setErreur(15,utf8_encode("j'ai pas envie d'aller à l'école")));

$response = $client->__getLastResponse();
$xml = simplexml_load_string($response);

$dom = new DOMDocument('1.0');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xml->asXML());
echo $dom->saveXML();
