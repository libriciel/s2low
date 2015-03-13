<?php

$server = "https://192.168.1.28:4443/";

$ch = curl_init();
	
//curl_setopt($ch, CURLOPT_URL, $server. "/admin/authorities/admin_authority_edit_handler.php");
curl_setopt($ch, CURLOPT_URL, $server. "/admin/users/admin_user_edit_handler.php");

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

$data = array(
"certificate" => "@/etc/tedetis/ssl/tedetis_timestamp_cert.pem",
'api' => '1',
'name' => 'First',
'givenname' => 'Prenom',
'email' => 'p.viver@adullact.org',
'status' => '1',
'role' => 'USER',
'perm_1' => 'RW');



curl_setopt($ch, CURLOPT_POSTFIELDS,$data);


$data = curl_exec($ch);

if (! $data ){
	echo curl_error($ch);
} 
curl_close($ch);

echo $data;
