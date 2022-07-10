<?php

require_once("../include/init-module-mail.php");

$db = DatabasePool::getInstance();
$mailList = new MailList($db, $me->getId());

echo $mailList->getNb();
