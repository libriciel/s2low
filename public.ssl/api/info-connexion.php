<?php

$_GET['api'] = 1;
require_once(__DIR__ . "/../../init/init-www.php");

$info['user_info'] = $userInfo;
unset($info['user_info']['password']);

$ok = ['id','authority_type_id','status','name','email','address','postal_code','city','telephone','department','district','authority_group_id'];

foreach ($ok as $key) {
    $info['authority_info'][$key] = $authorityInfo[$key];
}

$json = json_encode($info, JSON_PRETTY_PRINT);

echo $json;
