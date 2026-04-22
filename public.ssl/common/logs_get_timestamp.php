<?php

/**
 * \file logs_get_timestamp.php
 * \brief Page de téléchargement de l'entrée de logs accompagnée de son horodatage
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 21.08.2006
 *
 *
 * Ce script permet de télécharger une archive zip contenant l'entrée de journal
 * accompagnée de son fichier d'horodatage.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\User;

$me = new User();

if (! $me->authenticate()) {
    $_SESSION['error'] = "Échec de l'authentification";
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('connexion-status'));
    exit();
}

try {
    $log_id = \S2lowLegacy\Class\Helpers\RequestHelper::getIntFromGet('id');
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/common/logs_view.php'));
    exit();
}

$my_authority_id = new Authority($me->get('authority_id'));

$log = new Log();

if (! empty($log_id)) {
    $log->setId($log_id);
    if (! $log->init()) {
        $_SESSION['error'] = "Erreur lors de l'initialisation de l'entrée de journal.";
        header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/common/logs_view.php'));
        exit();
    }
} else {
    $_SESSION['error'] = "Pas d'identifiant de log spécifié.";
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/common/logs_view.php'));
    exit();
}

// Vérification des permissions sur l'entrée de journal
if (! $log->canView($me)) {
    $_SESSION['error'] = 'Accès refusé.';
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/common/logs_view.php'));
    exit();
}

if (! $log->sendArchive()) {
    $_SESSION['error'] = "Erreur de récupération de l'entrée de log et de son horodatage.<br />" . $log->getErrorMsg();
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/common/logs_view.php'));
}

exit();
