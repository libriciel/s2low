<?php

use S2lowLegacy\Class\actes\ActesScriptHelper;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\Droit;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Lib\Recuperateur;

/** @var Initialisation $initialisation */
/** @var Droit $droit */
/** @var ActesScriptHelper $actesScriptHelper */
/** @var UserContext $userContext */

[$initialisation,$droit ,$actesScriptHelper, $userContext] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, Droit::class,ActesScriptHelper::class, UserContext::class]);

$initialisation->initModule($userContext, Initialisation::MODULENAMEACTES, Initialisation::DROITSACTES);

if (! $droit->isSuperAdmin($userContext->userInfo)) {
    header('Location: index.php');
    exit;
}
$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->get('id');
$userMessage = $recuperateur->get('message');

$message = 'Transaction passée manuellement en erreur';
if ($userMessage !== false) {
    $message .= ' - ' . $userMessage;
}

$actesScriptHelper->updateStatusAndLog(
    [$id],
    ActesStatusSQL::STATUS_EN_ERREUR,
    $message
);



$_SESSION['error'] = "La transaction $id a été passée en erreur.";
header_wrapper("Location: actes_transac_show.php?id=$id");
exit_wrapper();
