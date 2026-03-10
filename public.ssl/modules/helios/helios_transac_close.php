<?php

use S2lowLegacy\Class\helios\HeliosPrepareEnvoiSAE;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\UserContext;

/** @var Initialisation $initialisation */
/** @var HeliosPrepareEnvoiSAE $heliosArchiveControler */
/** @var UserContext $userContext */

[$initialisation,$heliosArchiveControler, $userContext] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class,HeliosPrepareEnvoiSAE::class, UserContext::class]);

$initialisation->initModule($userContext, Initialisation::MODULENAMEHELIOS);

$liste_id = Helpers::getVarFromPost('liste_id');

$msg = '';

foreach ($liste_id as $id) {
    $id_d = $heliosArchiveControler->setArchiveEnAttenteEnvoiSEA($userContext->connexion->getId(), $id);
    if ($id_d) {
        $msg = "Programmation de l'envoi de la transaction $id à Pastell";
    } else {
        $msg .= "Erreur lors de l'envoi de la transaction $id à Pastell: " .
            $heliosArchiveControler->getLastError() . "\n";
    }
    if (
        ! Log::newEntry(
            LOG_ISSUER_NAME,
            $msg,
            1,
            false,
            'USER',
            'helios',
            false,
            $userContext->connexion->getId()
        )
    ) {
        $msg .= "Erreur de journalisation.\n";
    }
}


$_SESSION['error'] = nl2br($msg);
header_wrapper('Location: index.php');
exit_wrapper();
