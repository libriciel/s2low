<?php

require_once("../include/init.php");

$db = DatabasePool::getInstance();
$mailList = new MailList($db, $me->getId());

echo $mailList->getNb();
