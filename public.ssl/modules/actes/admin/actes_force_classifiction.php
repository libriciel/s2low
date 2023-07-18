<?php

use S2lowLegacy\Class\actes\ActesClassificationCreation;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\Recuperateur;

/** @var Initialisation $init */
$init = LegacyObjectsManager::getLegacyObjectInstancier()->get(Initialisation::class);

$init->initActes();

if (!$init->userIsSuperAdmin()) {
    $_SESSION["error"] = "Super admin only !";
    header("Location: " . WEBSITE);
    exit();
}

$recuperateur = new Recuperateur($_GET);
$authority_id = $recuperateur->getInt('authority_id');
$force = $recuperateur->getInt('force');


$authority = new Authority($authority_id);
$authority->init();

$classificationCreation = new ActesClassificationCreation();
$classificationCreation->unsetFrequencyRestriction();


$result = $classificationCreation->createEnveloppe($authority, null, $force);
Helpers::returnAndExit(
    ! $result,
    $classificationCreation->getLastMessage(),
    Helpers::getLink("/admin/authorities/admin_authority_edit.php?id=$authority_id")
);
