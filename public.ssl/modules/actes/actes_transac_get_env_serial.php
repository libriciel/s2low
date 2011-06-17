<?php
require_once(dirname(__FILE__)."/../../../init/init-www-actes.php");

verifIsGroupAdminOrSuper($me);
verifModePapier($module);

require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelopeSerialSQL.class.php');

$authority_id = $me->get("authority_id");

$actesEnvelopeSerial = new ActesEnvelopeSerialSQL(DatabasePool::getInstance());
$serialNumber = $actesEnvelopeSerial->getNext($authority_id);

if ( ! $serialNumber) {
	 echo "KO\nErreur récupération numéro de série\n";
	 exit;
}

echo "OK\n" . $serialNumber . "\n";