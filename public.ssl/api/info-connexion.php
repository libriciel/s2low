<?php

use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Lib\SQLQuery;

$_GET['api'] = 1;

/** @var Initialisation $initialisation */
/** @var SQLQuery $sqlQuery */
/** @var UserContext $userContext */

[$initialisation, $sqlQuery, $userContext] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, SQLQuery::class, UserContext::class]);

$info['user_info'] = $userContext->userInfo;
unset($info['user_info']['password']);

$ok = ['id','authority_type_id','status','name','email','address','postal_code','city','telephone','department','district','authority_group_id'];

foreach ($ok as $key) {
    $info['authority_info'][$key] = $userContext->authorityInfo[$key];
}

$json = json_encode($info, JSON_PRETTY_PRINT);

echo $json;
