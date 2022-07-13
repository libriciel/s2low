<?php

list($module, $me, $myAuthority) = MailInit::getIdentificationParameters();

$db = DatabasePool::getInstance();
$mailList = new MailList($db, $me->getId());

echo $mailList->getNb();
