<?php

// Instanciation du module courant
use S2lowLegacy\Class\actes\ActesEnvelopeSerialSQL;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName("actes");
if (!$module) {
    echo "KO\nErreur d'initialisation du module";
    exit();
}

$me = new User();

if (! $me->authenticate()) {
    echo "KO\nÉchec de l'authentification";
    exit();
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive() || !$me->checkDroit($module->get("name"), 'CS')) {
    echo "KO\nAccès refusé";
    exit();
}

$myAuthority = new Authority($me->get("authority_id"));

$authority_id = $me->get("authority_id");

$actesEnvelopeSerial = new ActesEnvelopeSerialSQL(DatabasePool::getInstance());
$serialNumber = $actesEnvelopeSerial->getNext($authority_id);

if (! $serialNumber) {
     echo "KO\nErreur récupération numéro de série\n";
     exit;
}

echo "OK\n" . $serialNumber . "\n";
