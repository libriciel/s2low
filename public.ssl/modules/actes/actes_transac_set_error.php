<?php

use S2lowLegacy\Class\actes\ActesScriptHelper;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\Droit;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\Recuperateur;

/** @var Initialisation $initialisation */
/** @var Droit $droit */
/** @var ActesScriptHelper $actesScriptHelper */

[$initialisation,$droit ,$actesScriptHelper] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, Droit::class,ActesScriptHelper::class]);

$initData = $initialisation->doInit(Initialisation::MODULENAMEACTES, Initialisation::DROITSACTES);

if (! $droit->isSuperAdmin($initData->userInfo)) {
    header('Location: index.php');
    exit;
}
$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->get('id');

$actesScriptHelper->updateStatusAndLog(
    [$id],
    ActesStatusSQL::STATUS_EN_ERREUR,
    'Transaction passée manuellement en erreur'
);



$_SESSION['error'] = "La transaction $id a été passée en erreur.";
header_wrapper("Location: actes_transac_show.php?id=$id");
