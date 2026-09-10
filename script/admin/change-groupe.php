<?php

use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\AuthorityGroupSirenSQL;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\GroupSQL;

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);

if ($argc < 3) {
    echo "{$argv[0]} : Déplace toutes les collectivités appartenant au groupe old_group_id vers le groupe new_group_id\n";
    echo "Usage : {$argv[0]} old_group_id new_group_id\n";
    exit(-1);
}

$old_groupe_id = $argv[1];
$new_groupe_id = $argv[2];
$do = isset($argv[3]) ? ($argv[3] == 'do') : false;


$groupSQL = new GroupSQL($sqlQuery);

$old_group = $groupSQL->getInfo($old_groupe_id);


$new_group = $groupSQL->getInfo($new_groupe_id);

echo "Déplacement des collectivités du groupe  --{$old_group['name']}-- vers le groupe --{$new_group['name']}--\n";

$authoritySQL = new AuthoritySQL($sqlQuery);

$authorities_list = $authoritySQL->getAllAdministeredBy((int)$old_groupe_id);

if (! $authorities_list) {
    echo "Aucune collecitivité trouvée...\n";
    exit;
}

echo count($authorities_list) . " collectivités trouvées : \n";

echo "\t- " . implode("\n\t- ", $authorities_list) . "\n";

$autorityGroupSirenSQL = new AuthorityGroupSirenSQL($sqlQuery);


foreach ($authorities_list as $authority_id => $authority_name) {
    $authority_info = $authoritySQL->getInfo($authority_id);
    echo "Traitement de $authority_name ({$authority_info['siren']}): ";
    if ($do) {
        $autorityGroupSirenSQL->add($new_groupe_id, $authority_info['siren']);
        $sql = "UPDATE authorities SET authority_group_id=? WHERE id=?";
        $sqlQuery->query($sql, $new_groupe_id, $authority_id);
        echo "[OK]\n";
    } else {
        echo "[PASS]\n";
    }
}

if ($do) {
    echo "Traitement terminé\n";
} else {
    echo "Aucune modification n'a été faite. Faire la commande suivante pour faire les actions : \n";
    echo "{$argv[0]} {$argv[1]} {$argv[2]} do\n";
}
