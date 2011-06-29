<?php

require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isAdmin()) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

// Recuperation des variables du POST
$id = Helpers::getVarFromPost("id");
$mode = Helpers::getVarFromPost("mode");
$name = Helpers::getVarFromPost("name", true);
$status = Helpers::getVarFromPost("status", true);
$siren_file = $_FILES["siren_file"];

$group = new Group();
$mod = false;

if (isset($id) && ! empty($id)) {
  $group->setId($id);
  $mod = true;
  if (! $group->init()) {
    $_SESSION["error"] = "Erreur lors de la modification du groupe";
    header("Location: " . WEBSITE_SSL . "/admin/groups/admin_groups.php");
    exit();
  }
}

$group->set("name", $name);
$group->set("status", $status);

# Traitement de la liste des SIREN autorisés
if (isset($siren_file["tmp_name"]) && strlen($siren_file["tmp_name"]) > 0) {
  if (! is_uploaded_file($siren_file["tmp_name"])) {
    $_SESSION["error"] = "Envoi de fichier incorrect.";
    header("Location: " . WEBSITE_SSL . "/admin/groups/admin_groups.php");
    exit();
  }

  if (! $group->importSiren($siren_file["tmp_name"])) {
    $_SESSION["error"] = "Échec lors de l'import du fichier SIREN : " . $group->getErrorMsg();
    header("Location: " . WEBSITE_SSL . "/admin/groups/admin_groups.php");
    exit();
  }
}

if (! $group->save()) {
  $msg = "Erreur lors de l'enregistrement du groupe :\n" . $group->getErrorMsg();
  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3,false, $me->get("role"), false, $me)) {
	$msg .= "\nErreur de journalisation.";
  }

  $_SESSION["error"] = nl2br($msg);

  if ($group->isNew()) {
	header("Location: " . WEBSITE_SSL . "/admin/groups/admin_group_edit.php");
  } else {
	header("Location: " . WEBSITE_SSL . "/admin/groups/admin_group_edit.php?id=" . $group->getId());
  }

  exit();
} else {
  Helpers::purgeTempSession();
  $msg = ($mod) ? "Modification" : "Création";
  $msg .= " du groupe " . $group->get("name") . " (id=" . $group->getId() . "). Résultat ok.";
  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
	$msg .= "\nErreur de journalisation.";
  }

  $_SESSION["error"] = nl2br($msg);
  header("Location: " . WEBSITE_SSL . "/admin/groups/admin_group_edit.php?id=" . $group->getId());
}
?>