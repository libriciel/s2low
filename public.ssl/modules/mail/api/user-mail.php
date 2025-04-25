<?php

use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\MailInit;
use S2lowLegacy\Mail\Annuaire;

list($module, $me, $myAuthority) = MailInit::getIdentificationParameters();

$db = DatabasePool::getInstance();

$annuaire = new Annuaire($db, $me->get('authority_id'));
foreach ($annuaire->getAllMail() as $entry) {
    echo $entry['id'] . ":" . $entry['mail_address'] . ":" . $entry['description'];

    foreach ($entry['groupe'] as $groupe) {
        echo ":$groupe";
    }
    echo "\n";
}
