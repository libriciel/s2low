<?php 

require_once("../include/init.php");
require_once("../lib/MailList.class.php");

$db = DatabasePool::getInstance();
$mailList = new MailList($db,$me->getId());

$id = Helpers::getVarFromGet("id");
if (!$id){
	echo "Usage : ".WEBSITE_SSL."/modules/mail/api/detail-mail.php?id=xxxx";
	exit;
}

$detail = $mailList->getDetail($id);
if (!$detail){
	echo "ERROR: cette transaction n'existe pas";
	exit;
}

foreach (array('id','date_envoi','password','fn_download','status','objet') as $data){
	echo $data . ":" . $detail[$data]."\n";
}
foreach($detail['file'] as $file){
	echo "file:".$file['filesize'].":".$file['filetype'].":".$file['filename']."\n";
}

foreach($detail['mail_emis'] as $emis){
	echo "emis:".$emis['email'].":".$emis['type_envoi'].":".$emis['ack'].":".$emis['ack_date']."\n";
}
echo "\n\n==message==\n\n";
echo $detail['message'];