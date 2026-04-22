<?php

/**
 * \file admin_module_edit_handler.php
 * \brief Page de modification de modules
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 21.07.2006
 *
 *
 * Cette page permet d'activer ou désactiver un module et ajouter
 * ou modifier ses paramètres.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *  BF      26.07.2006  Modifications du handler
 */

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Éhec de l'authentification";
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
    exit();
}

if (! $me->isSuper()) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

//! Recuperation des variables du POST
//! Recuperation des informations sur le module
$id = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("id");
$status = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("status");

//! On récupère des informations sur les parametres du module sous forme de tableaux
$param_id = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("param_id");
$param_name = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("param_name");
$param_description = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("param_description");
$param_value = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("param_value");
$param_to_suppr = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("param_to_suppr");

//! On recupere les informations concernant le paramètre du module a ajouter
$new_param_name = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("new_param_name");
$new_param_value = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("new_param_value");
$new_param_description = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("new_param_description");

$modules = new Module();

if (isset($id) && ! empty($id)) {
    $modules->setId($id);
    if (! $modules->init()) {
        $_SESSION["error"] = "Erreur lors de la modification de la collectivité";
        header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/authorities/admin_modules.php"));
        exit();
    }
}

$modules->set("status", $status);

if (is_array($param_id) && count($param_id) > 0) {
    foreach ($param_id as $key => $value_id) {
        if (is_array($param_to_suppr)) {
            if (! in_array($value_id, $param_to_suppr)) {
                $modules->setModuleParams($param_name[$key], $param_description[$key], $param_value[$key]);
            }
        } else {
            $modules->setModuleParams($param_name[$key], $param_description[$key], $param_value[$key]);
        }
    }
}

if (mb_strlen($new_param_name) > 0 && mb_strlen($new_param_description) > 0 && mb_strlen($new_param_value) > 0) {
    $modules->setModuleParams($new_param_name, $new_param_description, $new_param_value);
}

if (! $modules->save()) {
    $msg = "Erreur lors de l'enregistrement du module :\n" . $modules->getErrorMsg();
    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, $me->get("role"), false, $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    $_SESSION["error"] = nl2br($msg);
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/modules/admin_modules.php"));
    exit();
} else {
    $msg = ($modules->isNew()) ? "Création" : "Modification";
    $msg .= " du module " . $modules->get("name") . " (id=" . $modules->getId() . "). Résultat ok.";
    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    $_SESSION["error"] = nl2br($msg);
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/modules/admin_module_edit.php?id=") . $modules->getId());
}

exit();
