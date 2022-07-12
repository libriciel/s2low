<?php

list($doc, $mailerSecurise) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([MailLayout::class, \S2low\Services\MailSecurises\MailSecuriseNotification::class]);
list($module, $me, $myAuthority) = MailInit::getIdentificationParameters();

if (isset($_POST['password'])) {
    $_POST['psw1'] = $_POST['password'];
    $_POST['psw2'] = $_POST['password'];
}

$_POST['FileNumber'] = count($_FILES);

$MailCtl = new MailController($me, $doc, $module, $myAuthority, $mailerSecurise);
ob_start();
$mailId = $MailCtl->executeSend();
ob_end_clean();

if ($mailId) {
    echo "OK:$mailId\n";
} else {
    $erreur = $MailCtl->getLastError();
    echo "ERROR:$erreur\n";
}
