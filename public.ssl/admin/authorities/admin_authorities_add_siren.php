<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\JSONoutput;
use S2lowLegacy\Lib\LuhnKey;
use S2lowLegacy\Lib\Siren;
use S2lowLegacy\Lib\SirenFactory;
use S2lowLegacy\Model\AuthorityGroupSirenSQL;

list($jsonOutput,$authorityGroupSirenSQL,$sirenFactory) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([JSONoutput::class, AuthorityGroupSirenSQL::class, SirenFactory::class]);

$me = new User();

if (! $me->authenticate()) {
        $jsonOutput->displayErrorAndExit("Echec de l'authentification");
}

if (! $me->isGroupAdminOrSuper()) {
        $jsonOutput->displayErrorAndExit("Acces refuse");
}

$authority_group_id = false;

if ($me->isSuper()) {
        $gid = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromGet("authority_group_id");
    if (!is_numeric($gid)) {
            $jsonOutput->displayErrorAndExit("identifiant groupe invalide");
    } else {
            $authority_group_id =  $gid;
    }
} else {
        $authority_group_id = $me->get("authority_group_id");
}
/** @var Siren $theSiren */
$theSiren  = $sirenFactory->get(\S2lowLegacy\Class\Helpers\RequestHelper::getVarFromGet("siren"));
if (! $theSiren->isValid()) {
        $jsonOutput->displayErrorAndExit("siren non valide");
}

if ($authorityGroupSirenSQL->exist($authority_group_id, $theSiren->getValue())) {
        $jsonOutput->displayErrorAndExit("siren deja present");
}
$authorityGroupSirenSQL->add($authority_group_id, $theSiren->getValue());
$result['status'] = 'ok';
$result['message'] = 'ajout reussi';
$jsonOutput->display($result);
