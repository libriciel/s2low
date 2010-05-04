<?php

/**
 * Permet de tester le fichiers actes_transac_set_archive_url
 */
 

$url_tedetis = 'https://dev.s2low-asoft.fr/modules/actes/actes_transac_set_archive_url.php'; 
$id = '22';
$url_archive = 'http://www.google.fr';


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url_tedetis);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);        
curl_setopt($ch, CURLOPT_HEADER, TRUE);
//curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSLCERT, "./user-cert.pem");
curl_setopt($ch, CURLOPT_SSLKEY, "./user-key.pem");
curl_setopt($ch,CURLOPT_SSLKEYPASSWD,"user");
curl_setopt($ch,CURLOPT_POST,true);
curl_setopt($ch, CURLOPT_POSTFIELDS, array('id'=>$id,'url'=>$url_archive));
// The usual - get the data and close the session
$data = curl_exec($ch);
if (! $data ){
	print curl_error($ch) ."\n";
} else {
	print $data;
}
print_r(curl_getinfo($ch));
curl_close($ch);

