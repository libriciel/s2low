<?php

use S2low\DTO\ModuleActivationRequest;
use S2low\Enum\AdministeredModule;
use S2low\Exceptions\GroupDesignationRefusedException;
use S2low\Security\Authorization\ModuleAdministration;
use S2low\Services\Authority\AdministeringGroupDesignation;
use S2low\Services\MailActesNotifications\MailerSymfony;
use S2lowLegacy\Class\actes\ActesConventions;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\FileUploader;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\JSONoutput;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\AuthorityGroupSirenSQL;
use S2lowLegacy\Model\AuthoritySQL;

list(
    $objectInstancier,
    $sqlQuery,
    $helios_use_passtrans_as_default,
    $moduleAdministration,
    $administeringGroupDesignation,
    $authorityGroupSirenSQL
) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [
            ObjectInstancier::class,
            SQLQuery::class,
            'app.helios_use_passtrans_as_default',
            ModuleAdministration::class,
            AdministeringGroupDesignation::class,
            AuthorityGroupSirenSQL::class,
        ]
    );

$me = new User();

$api = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("api");

if (! $me->authenticate()) {
    \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->exitOrDisplayError($api, "Échec de l'authentification", \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink("connexion-status"));
}

if (! $me->isAnyAdmin()) {
    \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->exitOrDisplayError($api, "Accès refusé", WEBSITE_SSL);
}

$id = null;
try {
    $id = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getIntFromPost("id", true);
} catch (Exception $exception) {
    \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->exitOrDisplayError($api, $exception->getMessage(), WEBSITE_SSL);
}

$name = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("name");
$siren = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("siren");
$agreement = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("agreement");
$email = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("email");
$defaultbroadcastEmail = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("default_broadcast_email");
$broadcastEmail = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("broadcast_email");
$status = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("status");
$authorityTypeId = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getIntFromPost("authority_type_id", true);
$address = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("address");
$postalCode = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("postal_code");
$city = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("city");
$department = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("department");
$district = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("district");
$telephone = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("telephone");
$fax = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("fax");
$helios_ftp_dest = preg_replace('/^\s+|\s+$/u', '', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("helios_ftp_dest")); // Delete les caracteres invisible en debut et fin de chaine
$email_mail_securise = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("email_mail_securise");
$descr_mail_securise = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("descr_mail_securise");
$helios_do_not_verify_nom_fic_unicity =
    \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromPost("helios_do_not_verify_nom_fic_unicity") === 't' ? true : false;
$newmailnotif = 'true';


$form_location =  \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink("/admin/authorities/admin_authority_edit.php?id=$id");

$authoritySQL = new AuthoritySQL($sqlQuery);

if (! $authoritySQL->verifDepartmentAndDistrict($department, $district)) {
    \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->exitOrDisplayError(
        $api,
        "Le code département ou le code arrondissement sont incorrects",
        $form_location
    );
}


$authority = new Authority();
$isAuthorityCreation = true;



if (isset($id) && ! empty($id)) {
    $authority->setId($id);
    $isAuthorityCreation = false;
    if (! $authority->init()) {
        \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->exitOrDisplayError(
            $api,
            "Erreur lors de la modification de la collectivité",
            $form_location
        );
    }
    $form_location =  \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink("/admin/authorities/admin_authority_edit.php?id=$id");
}




// Mode ajout => interdit aux admins simples
// et modif de sa collectivité uniquement
if (! $me->isGroupAdminOrSuper()) {
    if ($authority->isNew() || $authority->getId() != $me->get("authority_id")) {
        \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->exitOrDisplayError($api, "Accès refusé", $form_location);
    }
} elseif ($me->isGroupAdmin()) {
  // Si mode modif on vérifie que la collectivité appartient bien au groupe dont l'utilisateur est admin
    if (! $authority->isNew() && ! $authority->isInGroup($me->get("authority_group_id"))) {
        \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->exitOrDisplayError($api, "Accès refusé.", $form_location);
    }
}


//Vérification de l'email de la collectivité pour le module mail sec
if ($email_mail_securise && (  ! MailerSymfony::isValidMail($email_mail_securise) || mb_strstr($email_mail_securise, " "))) {
    if ($authority->isNew()) {
        $location = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink("/admin/authorities/admin_authorities.php");
    } else {
        $location = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink("/admin/authorities/admin_authority_edit.php?id=") . $authority->getId();
    }
    \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->exitOrDisplayError($api, "L'email " . get_hecho($email_mail_securise) . " n'est pas valide.", $location);
}


$isActesAdmin = $moduleAdministration->isActesAdmin((int)$id);
$isHeliosAdmin = $moduleAdministration->isHeliosAdmin((int)$id);

$savePerms = false;

if ($me->isGroupAdminOrSuper()) {
    $savePerms = true;
    $requeteHelper = LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class);

  // Module autorisés pour la collectivité
    $modules = Module::getActiveModulesList();
    $permsBeforeReset = $authority->getAuthorizedModules() ?: [];
    $authority->resetModulesPerms();

    // getModulePerm() relit la base tant qu'aucune permission n'a été posée : on retient ici ce que
    // le formulaire active, seule source fiable pour désigner les groupes.
    $activatedByModule = [];

    foreach ($modules as $module) {
        $moduleId = (int)$module["id"];

        // Le formulaire masque la case d'un module à qui ne l'administre pas, et une case masquée n'est pas
        // postée : on rend au module sa valeur d'avant la remise à zéro, sinon elle serait effacée.
        $checkboxWasHidden = ($moduleId === Module::ACTES && ! $isActesAdmin)
            || ($moduleId === Module::HELIOS && ! $isHeliosAdmin);

        $activatedByModule[$moduleId] = (bool)($checkboxWasHidden
            ? ($permsBeforeReset[$moduleId] ?? false)
            : $requeteHelper->getVarFromPost("perm_" . $moduleId));

        $authority->setModulePerm($moduleId, $activatedByModule[$moduleId]);
    }

    $chosenGroupIdByModule = [];

    foreach (AdministeredModule::cases() as $administeredModule) {
        $chosenGroupIdByModule[$administeredModule->value] =
            (int)$requeteHelper->getIntFromPost($administeredModule->groupColumn(), true);
    }

    try {
        $administeringGroups = $administeringGroupDesignation->resolve(
            new ModuleActivationRequest((int)$id, $activatedByModule, $chosenGroupIdByModule)
        );
    } catch (GroupDesignationRefusedException $exception) {
        \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->exitOrDisplayError($api, $exception->getMessage(), $form_location);
    }

    // Sans groupe administrateur, aucun ne se prononce sur le SIREN : il n'y a rien à contrôler.
    if ($administeringGroups->groupIds() !== []) {
        $availableSirens = $authorityGroupSirenSQL->getAvailableSirenForGroups($administeringGroups->groupIds(), (int)$id);

        if (! in_array($siren, $availableSirens, true)) {
            \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->exitOrDisplayError(
                $api,
                "Ce numéro de SIREN (" . $siren . ") n'est pas autorisé par les groupes qui administrent cette collectivité",
                $form_location
            );
        }
    }

    $authority->set("name", $name);
    $authority->set("siren", $siren);
    $authority->set("agreement", $agreement);
    $authority->set("status", $status);
    $authority->set("authority_type_id", $authorityTypeId);
    $authority->set("department", $department);
    $authority->set("district", $district);

    foreach (AdministeredModule::cases() as $administeredModule) {
        if ($administeringGroups->isDesignatedFor($administeredModule)) {
            $authority->set(
                $administeredModule->groupColumn(),
                $administeringGroups->groupIdFor($administeredModule)
            );
        }
    }

    if ($isHeliosAdmin) {
        $authority->set("helios_ftp_dest", $helios_ftp_dest);
    }
}

$authority->set("email", $email);
$authority->set("default_broadcast_email", $defaultbroadcastEmail);
$authority->set("broadcast_email", $broadcastEmail);
$authority->set("address", $address);
$authority->set("postal_code", $postalCode);
$authority->set("city", $city);
$authority->set("telephone", $telephone);
$authority->set("fax", $fax);
$authority->set("email_mail_securise", $email_mail_securise);
$authority->set("descr_mail_securise", $descr_mail_securise);
$authority->set("new_notification", $newmailnotif);

if ($isAuthorityCreation) {
    $authority->set('helios_use_passtrans', $helios_use_passtrans_as_default);
}

if (! $authority->save($savePerms)) {
    $msg = "Erreur lors de l'enregistrement de la collectivité&nbsp;:\n" . $authority->getErrorMsg();
    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, $me->get("role"), false, $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    if ($authority->isNew()) {
        $location = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink("/admin/authorities/admin_authorities.php");
    } else {
        $location =  \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink("/admin/authorities/admin_authority_edit.php?id=") . $authority->getId();
    }

    \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->exitOrDisplayError($api, nl2br($msg), $location);
}

if (isset($_FILES['convention_actes']) && $isActesAdmin) {
    $fileUploader = new FileUploader();
    if ($fileUploader->verifOK('convention_actes')) {
        $actesConventions = $objectInstancier->get(ActesConventions::class);

        $finfo = new finfo();
        if ($finfo->file($_FILES['convention_actes']['tmp_name'], FILEINFO_MIME_TYPE) == 'application/pdf') {
            $actesConventions->setConvention($authority->getId(), $_FILES['convention_actes']['tmp_name']);
        } else {
            \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->exitOrDisplayError(
                $api,
                nl2br("Erreur lors de la sauvegarde de la convention (PDF attendu)"),
                $location =  \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink("/admin/authorities/admin_authority_edit.php?id=") . $authority->getId()
            );
        }
    }
}

if ($me->isSuper()) {
    $authoritySQL->updateDoNotVerifyNomFicUnicity($authority->getId(), $helios_do_not_verify_nom_fic_unicity);
}

$msg = ($isAuthorityCreation) ? "Création" : "Modification";
$msg .= " de la collectivité " . $authority->get("name") . " (id=" . $authority->getId() . "). Résultat ok.";
if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
    $msg .= "\nErreur de journalisation.";
}

if ($api) {
    $jsonOutput = new JSONoutput();
    $jsonOutput->display(array('status' => 'ok','message' => $msg,'id' => $authority->getId()));
} else {
    $_SESSION["error"] = nl2br($msg);
    header("Location: " . \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink("/admin/authorities/admin_authority_edit.php?id=") . $authority->getId());
    exit;
}
