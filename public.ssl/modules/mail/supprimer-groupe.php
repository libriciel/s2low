<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\MailInit;
use S2lowLegacy\Mail\GroupeMail;

list($module, $me, $myAuthority) = MailInit::getIdentificationParameters();

if (! $me->isAuthorityAdmin()) {
        exit;
}
try {
    $groupe_id = \S2lowLegacy\Class\Helpers\RequestHelper::getIntFromGet('groupe_id');
} catch (Exception $e) {
    $_SESSION['last_error'] = $e->getMessage();
    header("Location: index.php?command=annuaire");
    exit;
}

$groupe = new GroupeMail($groupe_id);

$nb_user = $groupe->getNbUtilisateur();

$name = $groupe->get('name');

if ($nb_user) {
    $_SESSION['last_error'] = "Le groupe $name n'est pas vide !";
    header("Location: index.php?command=annuaire&groupe_id=$groupe_id");
    exit;
}

$groupe->delete();

$_SESSION['last_message'] = "Le groupe $name a été supprimé";
header("Location: index.php?command=annuaire");
exit();
