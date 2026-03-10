<?php

use S2lowLegacy\Class\Droit;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Model\HeliosTransactionsSQL;

/** @var Initialisation $initialisation */
/** @var Droit $droit */
/** @var \S2lowLegacy\Class\actes\TransactionSQL $transactionSQL */
/** @var UserContext $userContext */

[$initialisation,$droit,$transactionSQL, $userContext] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, Droit::class,HeliosTransactionsSQL::class, UserContext::class]);

$moduleData = $initialisation->initModule($userContext, Initialisation::MODULENAMEHELIOS);

if (! $droit->isSuperAdmin($userContext->userInfo)) {
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
        $userContext->userInfo['id']
    )
) {
    $msg .= "\nErreur de journalisation.";
}

$_SESSION['error'] = $msg;

header_wrapper('Location: index.php');
exit_wrapper();
