<?php 
require_once(dirname(__FILE__).'/../../../../config/config.php');

require_once(dirname(__FILE__)."/../lib/Annuaire.class.php");
require_once(dirname(__FILE__)."/../lib/GroupeMail.class.php");

require_once(SITEROOT . '/class/include.class.php');

$module = new Module();
if (!$module->initByName("mail")) {
  $_SESSION["error"] = "Erreur d'initialisation du module";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$me = new User();
  
if (!$me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit ();
}

if ($me->isGroupAdminOrSuper() || !$module->isActive() || !$me->canEdit($module->get("name"))) {
	$_SESSION["error"] = "Accès refusé";
	header("Location: " . WEBSITE_SSL);
	exit ();
}

$myAuthority = new Authority($me->get("authority_id"));
