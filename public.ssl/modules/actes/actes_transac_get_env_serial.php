<?php
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelopeSerialSQL.class.php');

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("actes")) {
  echo "KO\nErreur d'initialisation du module";
  exit();
}

$me = new User();

if (! $me->authenticate()) {
  echo "KO\nÉchec de l'authentification";
  exit();
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive() || !$me->canEdit($module->get("name"))) {
  echo "KO\nAccès refusé";
  exit();
}

$myAuthority = new Authority($me->get("authority_id"));

$authority_id = $me->get("authority_id");

$actesEnvelopeSerial = new ActesEnvelopeSerialSQL(DatabasePool::getInstance());
$serialNumber = $actesEnvelopeSerial->getNext($authority_id);

if ( ! $serialNumber) {
	 echo "KO\nErreur récupération numéro de série\n";
	 exit;
}

echo "OK\n" . $serialNumber . "\n";