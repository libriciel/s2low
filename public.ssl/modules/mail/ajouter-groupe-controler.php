<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\MailInit;
use S2lowLegacy\Mail\GroupeMail;

list($module, $me, $myAuthority) = MailInit::getIdentificationParameters();
if (! $me->isAuthorityAdmin()) {
        exit;
}
$name = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost('name');

if (! $name) {
    $_SESSION['error'] = "Le nom du groupe ne doit pas être vide !";
    header("Location: ajouter-groupe.php");
    exit;
}

$groupe = new GroupeMail();

$id = $groupe->getGroupeIdFromName($name, $me->get('authority_id'));
if ($id) {
    $_SESSION['error'] = "Ce groupe existe déjà !";
    header("Location: index.php?command=annuaire");
    exit;
}

$groupe->set("authority_id", $me->get('authority_id'));
$groupe->set('name', $name);
$groupe->save(false);

$_SESSION['message_ok'] = "Groupe $name crée avec succès";
header("Location: index.php?command=annuaire");
