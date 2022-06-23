<?php

require_once("../include/init.php");
require_once("../lib/MailList.class.php");
require_once(MAIL_SITEROOT . "/om/MailTransaction.class.php");

$db = DatabasePool::getInstance();
$mailList = new MailList($db, $me->getId());

$id = Helpers::getVarFromGet("id");
if (!$id) {
    echo "Usage : " . WEBSITE_SSL . "/modules/mail/api/detail-mail.php?id=xxxx";
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
echo cp1252_to_iso88591($detail['message']);
