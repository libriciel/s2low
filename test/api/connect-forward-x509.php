<?php

$url = "https://192.168.1.28:4443/modules/actes/actes_transac_get_status.php?api=1&transaction=173";

$custom_header_name = "org.s2low.forward-x509-identification";

$certificat_authentification = "user1.pem";
$private_key_authentification = "user1-key.pem";


$certicat_identification_pem = file_get_contents("Eric_Pommateau_RGS_2_etoiles.pem");
$certicat_identification_der = pem2der($certicat_identification_pem);
$certicat_identification= base64_encode($certicat_identification_der);


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSLCERT, $certificat_authentification);
curl_setopt($ch, CURLOPT_SSLKEY, $private_key_authentification);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch, CURLOPT_VERBOSE, 1);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("$custom_header_name: $certicat_identification"));


$data = curl_exec($ch);

if (! $data ){
	echo curl_error($ch);
}
curl_close($ch);

echo $data;


function pem2der($pem_data) {
	$begin = "CERTIFICATE-----";
	$end   = "-----END";
	$pem_data = substr($pem_data, strpos($pem_data, $begin)+strlen($begin));
	$pem_data = substr($pem_data, 0, strpos($pem_data, $end));
	$der = base64_decode($pem_data);
	return $der;
}
