<?php

use S2lowLegacy\Class\actes\TransactionSQL;
use S2lowLegacy\Class\Droit;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Lib\Recuperateur;

/** @var Initialisation $initialisation */
/** @var Droit $droit */
/** @var TransactionSQL $transactionSQL */

[$initialisation,$droit,$transactionSQL] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class,Droit::class,TransactionSQL::class]);

$initData = $initialisation->doInit();
$moduleData = $initialisation->initModule($initData, Initialisation::MODULENAMEACTES, Initialisation::DROITSACTES);

if (! $droit->isSuperAdmin($initData->userInfo)) {
    header('Location: index.php');
    exit;
}
$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->get('id');

$transactionSQL->delete($id);

$msg = "La transaction $id a été éradiquée ....";


if (
    !Log :: newEntry(
        LOG_ISSUER_NAME,
        $msg,
        1,
        false,
        'USER',
        $moduleData->module_name,
        null,
        $initData->userInfo['id']
    )
) {
    $msg .= "\nErreur de journalisation.";
}

$_SESSION['error'] = $msg;
header_wrapper('Location: index.php');
