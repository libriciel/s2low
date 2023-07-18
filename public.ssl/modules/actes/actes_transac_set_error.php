<?php

use S2lowLegacy\Class\actes\ActesScriptHelper;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\Recuperateur;

/** @var Initialisation $init */
/** @var ActesScriptHelper $actesScriptHelper */
list($init, $actesScriptHelper ) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [Initialisation::class, ActesScriptHelper::class]
    );

$init->initActes();

if (! $init->userIsSuperAdmin()) {
    header("Location: index.php");
    exit;
}
$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->get('id');

$actesScriptHelper->updateStatus(
    [$id],
    ActesStatusSQL::STATUS_EN_ERREUR,
    "Transaction passée manuellement en erreur"
);



$_SESSION['error'] = "La transaction $id a été passée en erreur.";
header("Location: actes_transac_show.php?id=$id");
