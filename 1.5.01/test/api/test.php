<?php

$server = "https://192.168.1.5/";

$ch = curl_init();
	
curl_setopt($ch, CURLOPT_URL, $server. "/admin/users/admin_user_detail.php?id=8");

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSLCERT,"admin-cert.pem");
curl_setopt($ch, CURLOPT_SSLKEY, "admin-key.pem");
curl_setopt($ch, CURLOPT_USERPWD, "epommate:winfield"); 
 
curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);


$data = curl_exec($ch);

if (! $data ){
	echo curl_error($ch);
} 
curl_close($ch);

echo $data;
