<?php

use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\MailInit;
use S2lowLegacy\Class\XMLHelper;
use S2lowLegacy\Mail\MailList;

list($module, $me, $myAuthority) = MailInit::getIdentificationParameters();

$db = DatabasePool::getInstance();
$mailList = new MailList($db, $me->getId());

$id = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromGet("id");
if (!$id) {
    echo "Usage : " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/mail/api/detail-mail.php?id=xxxx");
    exit;
}

header("content-type: text/plain; charset=utf-8");


$detail = $mailList->getDetail($id);
if (!$detail) {
    echo "ERROR: cette transaction n'existe pas";
    exit;
}

foreach (array('id','date_envoi','password','fn_download','status','objet') as $data) {
    echo $data . ":" . $detail[$data] . "\n";
}
foreach ($detail['file'] as $file) {
    echo "file:" . $file['filesize'] . ":" . $file['filetype'] . ":" . $file['filename'] . "\n";
}

foreach ($detail['mail_emis'] as $emis) {
    echo "emis:" . $emis['email'] . ":" . $emis['type_envoi'] . ":" . ($emis['ack'] ? 't' : 'f') . ":" . $emis['ack_date'] . "\n";
}
echo "\n\n==message==\n\n";

echo XMLHelper::cp1252_to_iso88591($detail['message']);
