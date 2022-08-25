<?php

use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\UserSQL;

require_once(__DIR__ . "/../../init/init.php");
$userSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(UserSQL::class);

echo "Debut du script de mise à jour des empreintes des certificats\n";
$userSQL->fixCerticateFingerprint(new X509Certificate());
echo "Fin du script de mise à jour des empreintes des certificats\n";
