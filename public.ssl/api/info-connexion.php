<?php

use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;

$_GET['api'] = 1;

/** @var Initialisation $init */
$init = LegacyObjectsManager::getLegacyObjectInstancier()->get(Initialisation::class);
$init->init();
$info['user_info'] = $init->getUserInfo();
unset($info['user_info']['password']);

$ok = ['id','authority_type_id','status','name','email','address','postal_code','city','telephone','department','district','authority_group_id'];

foreach ($ok as $key) {
    $info['authority_info'][$key] = $init->getAuthorityInfo()[$key];
}

$json = json_encode($info, JSON_PRETTY_PRINT);

echo $json;
