<?php

use S2lowLegacy\Class\actes\ActesPrepareEnvoiSAE;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Lib\Recuperateur;

/** @var Initialisation $initialisation */
/** @var ActesPrepareEnvoiSAE $actesPrepareEnvoiSAE */
/** @var UserContext $userContext */

[$initialisation,$actesPrepareEnvoiSAE, $userContext] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class,ActesPrepareEnvoiSAE::class, UserContext::class]);

$initialisation->initModule($userContext, Initialisation::MODULENAMEACTES, Initialisation::DROITSACTES);

$recuperateur = new Recuperateur($_POST);
$id = $recuperateur->getInt('id');

$result = $actesPrepareEnvoiSAE->setArchiveEnAttenteEnvoiSEA($userContext->connexion->getId(), $id);

if (! $result) {
    $_SESSION['error'] = 'Erreur: ' . $actesPrepareEnvoiSAE->getLastError();
    header("Location: actes_transac_show.php?id=$id");
    exit;
}

$msg = "Programmation de l'envoi de la transaction $id à Pastell";

$_SESSION['error'] = $msg;

if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', "actes", false, $userContext->connexion->getId())) {
    $_SESSION['error'] .= "\nErreur de journalisation.\n";
}

header("Location: actes_transac_show.php?id=$id");
