<?php

use S2lowLegacy\Class\actes\ActesPrepareEnvoiSAE;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Lib\Recuperateur;

/** @var Initialisation $init */
/** @var ActesPrepareEnvoiSAE $actesPrepareEnvoiSAE */
[$init,$actesPrepareEnvoiSAE] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [Initialisation::class,ActesPrepareEnvoiSAE::class]
    );

$init->initActes();

$recuperateur = new Recuperateur($_POST);
$id = $recuperateur->getInt('id');

$result = $actesPrepareEnvoiSAE->setArchiveEnAttenteEnvoiSEA($init->getUserId(), $id);

if (! $result) {
    $_SESSION['error'] = "Erreur: " . $actesPrepareEnvoiSAE->getLastError();
    header("Location: actes_transac_show.php?id=$id");
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
        "actes",
        false,
        $init->getUserId()
    )
) {
    $_SESSION['error'] .= "\nErreur de journalisation.\n";
}

header("Location: actes_transac_show.php?id=$id");
