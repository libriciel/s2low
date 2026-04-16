<?php

use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\MailInit;
use S2lowLegacy\Mail\MailList;
use S2lowLegacy\Mail\MailPeer;

list($module, $me, $myAuthority) = MailInit::getIdentificationParameters();

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
