<?php

$url = "https://192.168.1.28:4443/modules/actes/actes_transac_get_status.php?api=1&transaction=173";

$custom_header_name = "org.s2low.forward-x509-identification";



$cert = file_get_contents(__DIR__."/Eric_Pommateau_RGS_2_etoiles.pem");
$der = pem2der($cert);

$x509_forward = base64_encode($der);


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSLCERT,"user1.pem");
curl_setopt($ch, CURLOPT_SSLKEY, "user1-key.pem");

curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch, CURLOPT_VERBOSE, 1);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("$custom_header_name: ".$x509_forward));



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
