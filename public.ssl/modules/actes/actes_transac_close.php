<?php

require_once(__DIR__ . "/../../../init/init-www-actes.php");

require_once("../../../init/init.php");
$actesPrepareEnvoiSAE = LegacyObjectsManager::getLegacyObjectInstancier()->get(ActesPrepareEnvoiSAE::class);

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
    Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

$sortie = "";

if (!$me->authenticate()) {
    Helpers::returnAndExit(1, "Échec de l'authentification", WEBSITE);
}

if (! $me->isGroupAdminOrSuper() && (!$module->isActive() || !$me->checkDroit($module->get("name"), 'CS'))) {
    Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$liste_id = array ();

if (Helpers::getVarFromPost("id") != null) {
    $liste_id[] = Helpers::getVarFromPost("id");
} else {
    $liste_id = Helpers::getVarFromPost("liste_id");
}

$status = Helpers::getVarFromPost("status");
$types = ActesTransaction::getStatusList();
$myAuthority = new Authority($me->get("authority_id"));

if ($status == "valid") {
    $new_status_id = 5;
} elseif ($status == "invalid") {
    $new_status_id = 6;
} elseif ($status == "sae") {
    $new_status_id = 19;
} else {
    Helpers::returnAndExit(1, "État incorrect.", Helpers::getLink("/modules/actes/index.php"));
}


if ($status != 'sae' && $me->isGroupAdminOrSuper()) {
    Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

foreach ($liste_id as $id) {
    $trans = new ActesTransaction();
    $trans->setId($id);
    if ($trans->init()) {
        $owner = new User($trans->get("user_id"));
        $owner->init();
    } else {
        Helpers::returnAndExit(1, "Erreur d'initialisation de la transaction.", Helpers::getLink("/modules/actes/index.php"));
    }

    if ($trans->get("type") != 1) {
        Helpers::returnAndExit(1, "Ce type de transaction ne peut pas être cloturé.", Helpers::getLink("/modules/actes/actes_transac_show.php?id=") . $rel_trans->getId());
    }

    if (! in_array($trans->get('last_status_id'), array(4,5,14,20,18))) {
        $last_status_id = $trans->get('last_status_id');
        $sortie .= "Cette transaction $id ne peut pas encore être clôturée (statut $last_status_id)\n";
        continue;
    }

    if ($trans->get('last_status_id') == 18 && $new_status_id != 6) {
        $sortie .= "La transaction $id en attente de signature peut seulement être rejeté\n";
        continue;
    }

    if (! $trans->canValidate() && $new_status_id != 19 && $trans->get('last_status_id') != 18) {
        $sortie .= "Cette transaction $id ne peut pas encore être clôturée\n";
        continue;
    }

    if (in_array($new_status_id, [5,6]) && in_array($trans->get('last_status_id'), [5,6])) {
        $sortie .= "La transaction $id est déjà terminée et ne peut l'être de nouveau directement\n";
        continue;
    }


    $envelope = new ActesEnvelope($trans->get("envelope_id"));
    $envelope->init();

    // Vérification des permissions
    if ($status != 'sae' && (!($me->isAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && !($me->getId() == $envelope->get("user_id") && $me->checkDroit($module->get("name"), 'CS')))) {
        Helpers::returnAndExit(1, "Accès refusé.", Helpers::getLink("/modules/actes/index.php"));
    }

    if ($new_status_id == 19) {
        $result = $actesPrepareEnvoiSAE->setArchiveEnAttenteEnvoiSEA($me->getId(), $id);
        if ($result) {
            $msg = "Programmation de l'envoi de la transaction $id à Pastell\n";
            $severity = 1;
            $status = 0;
        } else {
            $msg = "Erreur lors de l'envoi de la transaction $id à Pastell : " . $actesPrepareEnvoiSAE->getLastError();
            $severity = 3;
            $status = 1;
        }
    } elseif (! $trans->setNewStatus($new_status_id, "Fermeture par l'utilisateur " . $me->getPrettyName())) {
        $msg = "Erreur lors de la tentative de passage de la transaction n°" . $trans->getId() . " vers l'état " . $types[$new_status_id] . ".\n";
        $severity = 3;
        $status = 1;
    } else {
        $msg = "Passage de la transaction n°" . $trans->getId() . " à l'état « " . $types[$new_status_id] . " ». Résultat ok.\n";
        $severity = 1;
        $status = 0;
    }

    $sortie .= $msg;
    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, $severity, false, 'USER', $module->get("name"), $me)) {
        $sortie .= "\nErreur de journalisation.\n";
    }
}

if (count($liste_id) == 1) {
    $retour = Helpers::getLink("/modules/actes/actes_transac_show.php?id=") . $liste_id[0];
} else {
    $retour = Helpers::getLink("/modules/actes/index.php");
}

Helpers::returnAndExit($status, $sortie, $retour);
