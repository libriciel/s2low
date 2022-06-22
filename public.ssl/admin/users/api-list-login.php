<?php

require_once(__DIR__ . "/../../../init/init.php");

$me = new User();
$certificateInfo = $me->getCertificateInfo();

$userSQL = new UserSQL($sqlQuery);
$all_user = $userSQL->getInfoFromCertificateInfo($certificateInfo);
foreach ($all_user as $user) {
    echo $user['login'] . "\n";
}
