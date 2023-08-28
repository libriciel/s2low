<?php

use S2lowLegacy\Class\User;
use S2lowLegacy\Model\UserSQL;

$userSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(UserSQL::class);

$me = new User();
$certificateInfo = $me->getCertificateInfo();

$all_user = $userSQL->getInfoFromCertificateInfo($certificateInfo);

foreach ($all_user as $user) {
    echo mb_convert_encoding($user['login'], 'ISO-8859-1') . "\n";
}
