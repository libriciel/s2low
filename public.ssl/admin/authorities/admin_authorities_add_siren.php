<?php

require_once(__DIR__ . "/../../../init/init.php");
list($jsonOutput,$authorityGroupSirenSQL) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([JSONoutput::class, AuthorityGroupSirenSQL::class]);

$me = new User();

if (! $me->authenticate()) {
        $jsonOutput->displayErrorAndExit("Echec de l'authentification");
}

if (! $me->isGroupAdminOrSuper()) {
        $jsonOutput->displayErrorAndExit("Acces refuse");
}

$authority_group_id = false;

if ($me->isSuper()) {
        $gid = Helpers::getVarFromGet("authority_group_id");
    if (!is_numeric($gid)) {
            $jsonOutput->displayErrorAndExit("identifiant groupe invalide");
    } else {
            $authority_group_id =  $gid;
    }
} else {
        $authority_group_id = $me->get("authority_group_id");
}

$siren = Helpers::getVarFromGet("siren");
$theSiren  = new Siren(new LuhnKey());
if (VERIFICATION_SIREN) {
    if (! $theSiren->isValid($siren)) {
            $jsonOutput->displayErrorAndExit("siren non valide");
    }
}

if ($authorityGroupSirenSQL->exist($authority_group_id, $siren)) {
        $jsonOutput->displayErrorAndExit("siren deja present");
}
$authorityGroupSirenSQL->add($authority_group_id, $siren);
$result['status'] = 'ok';
$result['message'] = 'ajout reussi';
$jsonOutput->display($result);
