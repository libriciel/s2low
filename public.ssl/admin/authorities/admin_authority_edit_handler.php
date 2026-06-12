<?php

use S2low\Services\MailActesNotifications\MailerSymfony;
use S2lowLegacy\Class\actes\ActesConventions;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\FileUploader;
use S2lowLegacy\Class\Group;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\JSONoutput;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\AuthoritySQL;

list($objectInstancier, $sqlQuery, $helios_use_passtrans_as_default, $moduleSQL) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [ObjectInstancier::class, SQLQuery::class, 'app.helios_use_passtrans_as_default', \S2lowLegacy\Model\ModuleSQL::class]
    );

$me = new User();

$api = Helpers::getVarFromPost("api");

if (! $me->authenticate()) {
    Helpers::exitOrDisplayError($api, "Échec de l'authentification", Helpers::getLink("connexion-status"));
}

if (! $me->isAdmin()) {
    Helpers::exitOrDisplayError($api, "Accès refusé", WEBSITE_SSL);
}

$id = null;
try {
    $id = Helpers::getIntFromPost("id", true);
} catch (Exception $exception) {
    Helpers::exitOrDisplayError($api, $exception->getMessage(), WEBSITE_SSL);
}

$name = Helpers::getVarFromPost("name");
$siren = Helpers::getVarFromPost("siren");
$authorityGroupId = Helpers::getIntFromPost("authority_group_id", true);
$agreement = Helpers::getVarFromPost("agreement");
$email = Helpers::getVarFromPost("email");
$defaultbroadcastEmail = Helpers::getVarFromPost("default_broadcast_email");
$broadcastEmail = Helpers::getVarFromPost("broadcast_email");
$status = Helpers::getVarFromPost("status");
$authorityTypeId = Helpers::getIntFromPost("authority_type_id", true);
$address = Helpers::getVarFromPost("address");
$postalCode = Helpers::getVarFromPost("postal_code");
$city = Helpers::getVarFromPost("city");
$department = Helpers::getVarFromPost("department");
$district = Helpers::getVarFromPost("district");
$telephone = Helpers::getVarFromPost("telephone");
$fax = Helpers::getVarFromPost("fax");
$helios_ftp_dest = preg_replace('/^\s+|\s+$/u', '', Helpers::getVarFromPost("helios_ftp_dest")); // Delete les caracteres invisible en debut et fin de chaine
$email_mail_securise = Helpers::getVarFromPost("email_mail_securise");
$descr_mail_securise = Helpers::getVarFromPost("descr_mail_securise");
$helios_do_not_verify_nom_fic_unicity =
    Helpers::getVarFromPost("helios_do_not_verify_nom_fic_unicity") === 't' ? true : false;
$newmailnotif = 'true';


$form_location =  Helpers::getLink("/admin/authorities/admin_authority_edit.php?id=$id");

$authoritySQL = new AuthoritySQL($sqlQuery);

if (! $authoritySQL->verifDepartmentAndDistrict($department, $district)) {
    Helpers::exitOrDisplayError(
        $api,
        "Le code département ou le code arrondissement sont incorrects",
        $form_location
    );
}


$authority = new Authority();
$mod = false;



if (isset($id) && ! empty($id)) {
    $authority->setId($id);
    $mod = true;
    if (! $authority->init()) {
        Helpers::exitOrDisplayError(
            $api,
            "Erreur lors de la modification de la collectivité",
            $form_location
        );
    }
    $form_location =  Helpers::getLink("/admin/authorities/admin_authority_edit.php?id=$id");
}




// Mode ajout => interdit aux admins simples
// et modif de sa collectivité uniquement
if (! $me->isGroupAdminOrSuper()) {
    if ($authority->isNew() || $authority->getId() != $me->get("authority_id")) {
        Helpers::exitOrDisplayError($api, "Accès refusé", $form_location);
    }
} elseif ($me->isGroupAdmin()) {
  // Si mode modif on vérifie que la collectivité appartient bien au groupe dont l'utilisateur est admin
    if (! $authority->isNew() && ! $authority->isInGroup($me->get("authority_group_id"))) {
        Helpers::exitOrDisplayError($api, "Accès refusé.", $form_location);
    }

  // Vérification que le SIREN est bien autorisé pour ce groupe
    $group = new Group($me->get("authority_group_id"));

    $sirenList = $group->getAuthorizedSiren();

    if (array_search($siren, $sirenList) === false) {
        Helpers::exitOrDisplayError($api, "Ce numéro de SIREN (" . $siren . ") n'est pas autorisé pour le groupe " . $group->get("name"), $form_location);
    }

  // On force le authority_group_id à celui de l'admin du groupe
    $authorityGroupId = $me->get("authority_group_id");
}


//Vérification de l'email de la collectivité pour le module mail sec
if ($email_mail_securise && (  ! MailerSymfony::isValidMail($email_mail_securise) || mb_strstr($email_mail_securise, " "))) {
    if ($authority->isNew()) {
        $location = Helpers::getLink("/admin/authorities/admin_authorities.php");
    } else {
        $location = Helpers::getLink("/admin/authorities/admin_authority_edit.php?id=") . $authority->getId();
    }
    Helpers::exitOrDisplayError($api, "L'email " . get_hecho($email_mail_securise) . " n'est pas valide.", $location);
}


if ($me->isGroupAdminOrSuper()) {
    $authority->set("name", $name);
    $authority->set("siren", $siren);
    $authority->set("authority_group_id", $authorityGroupId);
    $authority->set("agreement", $agreement);
    $authority->set("status", $status);
    $authority->set("authority_type_id", $authorityTypeId);
    $authority->set("department", $department);
    $authority->set("district", $district);
    $authority->set("helios_ftp_dest", $helios_ftp_dest);
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

if (!$mod) {
    $authority->set('helios_use_passtrans', $helios_use_passtrans_as_default);
}

$savePerms = false;
if ($me->isGroupAdminOrSuper()) {
    $savePerms = true;
  // Module autorisés pour la collectivité
    $modules = $moduleSQL->getActiveModulesList();
    $authority->resetModulesPerms();

    foreach ($modules as $module) {
        $authority->setModulePerm($module["id"], Helpers::getVarFromPost("perm_" . $module["id"]));
    }
}

if (! $authority->save($savePerms)) {
    $msg = "Erreur lors de l'enregistrement de la collectivité&nbsp;:\n" . $authority->getErrorMsg();
    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, $me->get("role"), false, $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    if ($authority->isNew()) {
        $location = Helpers::getLink("/admin/authorities/admin_authorities.php");
    } else {
        $location =  Helpers::getLink("/admin/authorities/admin_authority_edit.php?id=") . $authority->getId();
    }

    Helpers::exitOrDisplayError($api, nl2br($msg), $location);
}


if (isset($_FILES['convention_actes']) && $me->isGroupAdminOrSuper()) {
    $fileUploader = new FileUploader();
    if ($fileUploader->verifOK('convention_actes')) {
        $actesConventions = $objectInstancier->get(ActesConventions::class);

        $finfo = new finfo();
        if ($finfo->file($_FILES['convention_actes']['tmp_name'], FILEINFO_MIME_TYPE) == 'application/pdf') {
            $actesConventions->setConvention($authority->getId(), $_FILES['convention_actes']['tmp_name']);
        } else {
            Helpers::exitOrDisplayError(
                $api,
                nl2br("Erreur lors de la sauvegarde de la convention (PDF attendu)"),
                $location =  Helpers::getLink("/admin/authorities/admin_authority_edit.php?id=") . $authority->getId()
            );
        }
    }
}

if ($me->isSuper()) {
    $authoritySQL->updateDoNotVerifyNomFicUnicity($authority->getId(), $helios_do_not_verify_nom_fic_unicity);
}

$msg = ($mod) ? "Modification" : "Création";
$msg .= " de la collectivité " . $authority->get("name") . " (id=" . $authority->getId() . "). Résultat ok.";
if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
    $msg .= "\nErreur de journalisation.";
}

if ($api) {
    $jsonOutput = new JSONoutput();
    $jsonOutput->display(array('status' => 'ok','message' => $msg,'id' => $authority->getId()));
} else {
    $_SESSION["error"] = nl2br($msg);
    header("Location: " . Helpers::getLink("/admin/authorities/admin_authority_edit.php?id=") . $authority->getId());
    exit;
}
