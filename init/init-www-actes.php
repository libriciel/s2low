<?php

require_once(dirname(__FILE__)."/../config/config.php");
require_once(SITEROOT . '/class/include.class.php');


// Instanciation du module courant
$module = new Module();
if (! $module->initByName("actes")) {
  $_SESSION["error"] = "Erreur d'initialisation du module";
  header("Location: " . WEBSITE_SSL);
  exit();
}


$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $module->isActive()|| ! $me->canAccess($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}


function verifIsGroupAdminOrSuper($me){
	if ($me->isGroupAdminOrSuper()) {
	  echo "KO\nAccès refusé";
	  exit();
	}
}

function verifModePapier($module){
	if ($module->getParam("paper") == "on") {
	  echo "KO\nMode « papier » actif. Accès interdit.";
	  exit();
	}
}

require_once(dirname(__FILE__)."/../class/SQLQuery.class.php");
$sqlQuery = new SQLQuery(DB_DATABASE);
$sqlQuery->setDatabaseHost(DB_HOST);
$sqlQuery->setCredential(DB_USER,DB_PASSWORD);


