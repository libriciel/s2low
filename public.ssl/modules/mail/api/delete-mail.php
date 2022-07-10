<?php

require_once("../include/init-module-mail.php");

$db = DatabasePool::getInstance();
$mailList = new MailList($db, $me->getId());

$id = Helpers::getVarFromGet("id");
if (!$id) {
    echo "Usage : " . Helpers::getLink("/modules/mail/api/delete-mail.php?id=xxxx");
    exit;
}


$detail = $mailList->getDetail($id);
if (!$detail) {
    echo "ERROR: cette transaction n'existe pas";
    exit;
}

MailPeer::DeleteMailTransation($id);


echo "OK: Transaction $id supprimé";
