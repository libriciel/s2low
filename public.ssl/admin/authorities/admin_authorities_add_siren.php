<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\JSONoutput;
use S2lowLegacy\Lib\LuhnKey;
use S2lowLegacy\Lib\Siren;
use S2lowLegacy\Model\AuthorityGroupSirenSQL;

list($jsonOutput,$authorityGroupSirenSQL) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
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
if (! $theSiren->isValid($siren)) {
        $jsonOutput->displayErrorAndExit("siren non valide");
}

if ($authorityGroupSirenSQL->exist($authority_group_id, $siren)) {
        $jsonOutput->displayErrorAndExit("siren deja present");
}
$authorityGroupSirenSQL->add($authority_group_id, $siren);
$result['status'] = 'ok';
$result['message'] = 'ajout reussi';
$jsonOutput->display($result);
