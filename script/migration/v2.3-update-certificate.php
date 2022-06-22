<?php

require_once(__DIR__ . "/../../init/init.php");


$userSQL = new UserSQL($sqlQuery);
echo "Debut du script de mise à jour des empreintes des certificats\n";
$userSQL->fixCerticateFingerprint(new X509Certificate());
echo "Fin du script de mise à jour des empreintes des certificats\n";