<?php

use S2lowLegacy\Class\helios\HeliosPrepareEnvoiSAE;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Lib\Recuperateur;

/** @var Initialisation $initialisation */
/** @var UserContext $userContext */

[$initialisation,$heliosArchiveControler, $userContext] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class,HeliosPrepareEnvoiSAE::class, UserContext::class]);

$initialisation->initModule($userContext, Initialisation::MODULENAMEHELIOS);

$recuperateur = new Recuperateur($_POST);
$id = $recuperateur->getInt('id');

$id_d = $heliosArchiveControler->setArchiveEnAttenteEnvoiSEA($userContext->connexion->getId(), $id);

if (! $id_d) {
    $_SESSION['error'] = 'Erreur: ' . $heliosArchiveControler->getLastError();
    header("Location: helios_transac_show.php?id=$id");
    exit;
}

$msg = "Programmation de l'envoi de la transaction $id à Pastell";

$_SESSION['error'] = $msg;

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
    $_SESSION['error'] .= "\nErreur de journalisation.\n";
}

header("Location: helios_transac_show.php?id=$id");
exit();
