<?php

$server = "https://192.168.1.28:4443/";

$ch = curl_init();
	
curl_setopt($ch, CURLOPT_URL, $server. "/admin/authorities/admin_authority_edit_handler.php");
//curl_setopt($ch, CURLOPT_URL, $server. "/admin/users/admin_user_edit_handler.php");

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
'api' => '1',
'siren'=>'427171996',
'name'=>'test  departement OK',
'authority_group_id'=>1,
'state'=>'1',
'authority_type_id'=>31,
'address'=>'231 toto ',
'postal_code'=>'68009',
'city'=>'Lyon',
'department'=>'001',
'district'=>'4',
'status' => 1,				

);
/*$authority->set("agreement", $agreement);
$authority->set("status", $status);
$authority->set("authority_type_id", $authorityTypeId);
$authority->set("department", $department);
$authority->set("district", $district);
$authority->set("helios_ftp_dest",$helios_ftp_dest);*/


curl_setopt($ch, CURLOPT_POSTFIELDS,$data);


$data = curl_exec($ch);

if (! $data ){
	echo curl_error($ch);
} 
curl_close($ch);

echo $data;
