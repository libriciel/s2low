<?php 


if (count($argv)>1){
	$certificate_path = $argv[1];
} else {
	$certificate_path = false;
}

if (!$certificate_path){
	echo "Usage : {$argv[0]} certificat.pem\n";
	echo "Retourne les informations sur le certificat\n";
	exit;
}

$content = file_get_contents($certificate_path);

$info = openssl_x509_parse($content);

print_r($info);