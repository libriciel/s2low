<?php

use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\MailInit;
use S2lowLegacy\Mail\MailList;

list($module, $me, $myAuthority) = MailInit::getIdentificationParameters();

$db = DatabasePool::getInstance();
$mailList = new MailList($db, $me->getId());

echo $mailList->getNb();
