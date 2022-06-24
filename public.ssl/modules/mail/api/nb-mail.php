<?php

require_once("../include/init.php");
require_once("../lib/MailList.class.php");

$db = DatabasePool::getInstance();
$mailList = new MailList($db, $me->getId());

echo $mailList->getNb();
