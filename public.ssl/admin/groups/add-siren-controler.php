<?php

use S2lowLegacy\Class\Droit;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Lib\SirenFactory;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\AuthorityGroupSirenSQL;
use S2lowLegacy\Model\GroupSQL;

/** @var  SirenFactory $sirenFactory */
/** @var Initialisation $initialisation */
/** @var SQLQuery $sqlQuery */
/** @var Droit $droit */
[$sirenFactory,$initialisation,$sqlQuery, $droit, $userContext] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([SirenFactory::class,Initialisation::class, SQLQuery::class, Droit::class, UserContext::class]);

if (! $droit->isSuperAdmin($userContext->userInfo)) {
    header('Location: index.php');
    exit;
}


$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->get('id');
$siren = $sirenFactory->get($recuperateur->get('siren'));

$authorityGroup = new GroupSQL($sqlQuery);

if (!(is_numeric($id) && floatval($id) == intval(floatval($id)))) {
    $_SESSION['error'] = "L'id $id fournie n'est pas un entier.";
    header('Location: ' . WEBSITE_SSL);
    exit;
}

if (empty($authorityGroup->getInfo($id))) {
    $_SESSION['error'] = "Le groupe $id n'existe pas.";
    header('Location: ' . WEBSITE_SSL);
    exit;
}

if (!$siren->isValid()) {
    $_SESSION['error'] = 'Le siren ' . $siren->getValue() . ' ne semble  pas valide.';
    header('Location: ' . Helpers::getLink("/admin/groups/admin_group_edit.php?id=$id"));
    exit;
}

$authorityGroupSirenSQL = new AuthorityGroupSirenSQL($sqlQuery);

if ($authorityGroupSirenSQL->exist($id, $siren->getValue())) {
    $_SESSION['error'] = 'Le siren existe déjà dans ce groupe';
    header('Location: ' . Helpers::getLink("/admin/groups/admin_group_edit.php?id=$id"));
    exit;
}

$authorityGroupSirenSQL->add($id, $siren->getValue());
$_SESSION['error'] = 'Le siren a été ajouté';
header('Location: ' . Helpers::getLink("/admin/groups/admin_group_edit.php?id=$id"));
exit;
