<?php

$server = "https://192.168.1.28:4443/";

$ch = curl_init();
	
curl_setopt($ch, CURLOPT_URL, $server. "/admin/authorities/admin_authority_edit_handler.php");

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSLCERT,"user.pem");
curl_setopt($ch, CURLOPT_SSLKEY, "user.key");
curl_setopt($ch, CURLOPT_SSLKEYPASSWD, "user");
curl_setopt($ch, CURLOPT_USERPWD, "epommate3:winfield"); 
curl_setopt($ch, CURLOPT_VERBOSE, 1);
curl_setopt($ch, CURLOPT_HEADER, 1); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,"api=1&authority_group_id=1&name=test1&siren=123456789&status=0&authority_type_id=11&department=001&district=1&perm_actes=0&address=28rueTruc&postal_code=01000&city=Bourg");


$data = curl_exec($ch);

if (! $data ){
	echo curl_error($ch);
} 
curl_close($ch);

echo $data;
