<?php

require_once("../include/init-module-mail.php");

if (isset($_POST['password'])) {
    $_POST['psw1'] = $_POST['password'];
    $_POST['psw2'] = $_POST['password'];
}

$_POST['FileNumber'] = count($_FILES);

$MailCtl = new MailController($me, $doc, $module, $myAuthority);
ob_start();
$mailId = $MailCtl->executeSend();
ob_end_clean();

if ($mailId) {
    echo "OK:$mailId\n";
} else {
    $erreur = $MailCtl->getLastError();
    echo "ERROR:$erreur\n";
}
