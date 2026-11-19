<?php

# Script permettant de récupérer le nombre de transactions actes et helios par groupe

use Psr\Log\LoggerInterface;
use S2low\Enum\AdministeredModule;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\AuthoritySQL;

require_once(__DIR__ . "/../../init/init.php");
list($s2LowLogger,$sqlQuery) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [LoggerInterface::class,SQLQuery::class]
    );
$authoritySQL = new AuthoritySQL($sqlQuery);

$date = date("Y-m-d");
$fileName = 'etatmensuel-' . $date . '.csv';
$filePath = '/data/log/' . $fileName;

$file = fopen($filePath, "w");

$sql = "select authority_groups.name,authority_group_id as id FROM users " .
   " JOIN authority_groups ON users.authority_group_id=authority_groups.id";

$sql = "select name, id FROM authority_groups ORDER BY id ASC";

$result = $sqlQuery->query($sql);


$sql_helios = "SELECT sum(file_size) FROM helios_transactions " .
    " WHERE authority_id=?";

$sql_actes = "SELECT sum(file_size) FROM actes_envelopes JOIN actes_transactions " .
    "ON actes_transactions.envelope_id=actes_envelopes.id " .
    "WHERE actes_transactions.authority_id=?";

$total_helios = 0;
$total_actes = 0;

foreach ($result as $group_info) {
    $group_helios_size = 0;
    $group_actes_size = 0;

    $administered = $authoritySQL->getAllAdministeredForModuleBy(AdministeredModule::HELIOS, (int)$group_info['id']);
    foreach (array_keys($administered) as $authority_id) {
        $group_helios_size += $sqlQuery->queryOne($sql_helios, $authority_id);
    }
    $administered = $authoritySQL->getAllAdministeredForModuleBy(AdministeredModule::ACTES, (int)$group_info['id']);
    foreach (array_keys($administered) as $authority_id) {
        $group_actes_size += $sqlQuery->queryOne($sql_actes, $authority_id);
    }

    fwrite($file, "{$group_info['id']}|{$group_info['name']}|$group_helios_size|$group_actes_size\n");
    $total_helios += $group_helios_size;
    $total_actes += $group_actes_size;
}

fwrite($file, "TOTAL (helios) : $total_helios\n");
fwrite($file, "TOTAL (actes) : $total_actes\n");

fclose($file);
