<?php

use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\SQLQuery;

$_GET['api'] = 1;

/** @var Initialisation $initialisation */
/** @var SQLQuery $sqlQuery */
[$initialisation, $sqlQuery] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, SQLQuery::class]);

$initData = $initialisation->doInit();

$info['user_info'] = $initData->userInfo;
unset($info['user_info']['password']);

$ok = ['id','authority_type_id','status','name','email','address','postal_code','city','telephone','department','district','authority_group_id'];

foreach ($ok as $key) {
    $info['authority_info'][$key] = $initData->authorityInfo[$key];
}

$json = json_encode($info, JSON_PRETTY_PRINT);

echo $json;
