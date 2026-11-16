<?php

use S2low\Enum\AdministeredModule;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\AuthorityGroupSirenSQL;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\GroupSQL;

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);

if ($argc < 3) {
    echo "{$argv[0]} : Déplace les collectivités administrées par le groupe old_group_id vers le groupe new_group_id\n";
    echo "Usage : {$argv[0]} old_group_id new_group_id [actes|helios] [do]\n";
    echo "Sans module, le groupe Actes et le groupe Helios sont tous deux réaffectés.\n";
    exit(-1);
}

$old_groupe_id = (int)$argv[1];
$new_groupe_id = (int)$argv[2];
$options = array_slice($argv, 3);
$do = in_array('do', $options, true);

$modules = AdministeredModule::cases();
foreach (AdministeredModule::cases() as $module) {
    if (in_array(strtolower($module->label()), $options, true)) {
        $modules = [$module];
    }
}


$groupSQL = new GroupSQL($sqlQuery);

$old_group = $groupSQL->getInfo($old_groupe_id);


$new_group = $groupSQL->getInfo($new_groupe_id);

$authoritySQL = new AuthoritySQL($sqlQuery);
$autorityGroupSirenSQL = new AuthorityGroupSirenSQL($sqlQuery);

foreach ($modules as $module) {
    echo "Module {$module->label()} : déplacement des collectivités du groupe  --{$old_group['name']}-- vers le groupe --{$new_group['name']}--\n";

    $authorities_list = $authoritySQL->getAllAdministeredForModuleBy($module, $old_groupe_id);

    if (! $authorities_list) {
        echo "Aucune collectivité trouvée...\n";
        continue;
    }

    echo count($authorities_list) . " collectivités trouvées : \n";

    echo "\t- " . implode("\n\t- ", $authorities_list) . "\n";

    foreach ($authorities_list as $authority_id => $authority_name) {
        $authority_info = $authoritySQL->getInfo($authority_id);
        echo "Traitement de $authority_name ({$authority_info['siren']}): ";
        if ($do) {
            if (! $autorityGroupSirenSQL->exist($new_groupe_id, $authority_info['siren'])) {
                $autorityGroupSirenSQL->add($new_groupe_id, $authority_info['siren']);
            }
            $authoritySQL->designateAdministeringGroup($authority_id, $module, $new_groupe_id);
            echo "[OK]\n";
        } else {
            echo "[PASS]\n";
        }
    }
}

if ($do) {
    echo "Traitement terminé\n";
} else {
    echo "Aucune modification n'a été faite. Faire la commande suivante pour faire les actions : \n";
    echo implode(' ', $argv) . " do\n";
}
