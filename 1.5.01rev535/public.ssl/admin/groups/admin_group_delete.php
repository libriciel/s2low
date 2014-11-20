<?php

require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

// Seul un super administrateur peut effectuer cette action
if (! $me->isSuper()) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$id = (isset($_POST["id"])) ? $_POST["id"] : null;

if (isset($id) && ! empty($id)) {
  $group = new Group($id);
  if ($group->delete()) {
	$msg = "Suppression du groupe " . $group->get("name") . ". Résultat ok.";
	if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
	  $msg .= "\nErreur de journalisation.";
	}
	
	$_SESSION["error"] = nl2br($msg);
    header("Location: " . WEBSITE_SSL . "/admin/groups/admin_groups.php");
    exit();
  } else {
	$msg = "Erreur lors de la tentative de suppression du groupe" . $group->getErrorMsg();
	if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, $me->get("role"), false, $me)) {
	  $msg .= "\nErreur de journalisation.";
	}
	
	$_SESSION["error"] = nl2br($msg);
    header("Location: " . WEBSITE_SSL . "/admin/groups/admin_groups.php");
    exit();
  }
} else {
  $_SESSION["error"] = "Pas d'identifiant de groupe spécifié";
  header("Location: " . WEBSITE_SSL . "/admin/groups/admin_groups.php");
  exit();
}
?>