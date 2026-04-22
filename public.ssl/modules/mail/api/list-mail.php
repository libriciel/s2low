<?php

use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\MailInit;
use S2lowLegacy\Mail\MailList;

list($module, $me, $myAuthority) = MailInit::getIdentificationParameters();

$db = DatabasePool::getInstance();
$mailList = new MailList($db, $me->getId());
$offset = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromGet("offset");
$limit = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromGet("limit");

?>
<?php foreach ($mailList->getListMail($offset, $limit) as $info) :?>
    <?php echo $info['id']?>:<?php echo $info['date_envoi']?>:<?php echo $info['status']?>:<?php echo $info['objet'] . "\n"?>
<?php endforeach?>
