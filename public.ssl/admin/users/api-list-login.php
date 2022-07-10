<?php

require_once(__DIR__ . "/../../../init/init.php");
$userSQL = LegacyObjectsManager::getLegacyObjectInstancier()->get(UserSQL::class);

$me = new User();
$certificateInfo = $me->getCertificateInfo();

$all_user = $userSQL->getInfoFromCertificateInfo($certificateInfo);
foreach ($all_user as $user) {
    echo $user['login'] . "\n";
}
