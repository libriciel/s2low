<?php

use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\MailInit;

if (empty($_GET['term'])) {
    return;
}

list($module, $me, $myAuthority) = MailInit::getIdentificationParameters();
$debut = urldecode($_GET['term']);

$bd = DatabasePool::getInstance();

$annuaire = new Annuaire($bd, $me->get('authority_id'));

$result = array();

foreach ($annuaire->getListeMailAndGroupe($debut) as $line) {
    $result[] = $line;
}

echo json_encode($result);
