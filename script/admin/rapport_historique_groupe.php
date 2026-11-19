<?php

use S2low\Enum\AdministeredModule;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\AuthoritySQL;

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);
$authoritySQL = new AuthoritySQL($sqlQuery);

function compte_actes($siren, $sqlQuery)
{
    $sql = "SELECT sum(file_size) as total,count(*) as nombre FROM actes_envelopes WHERE siren like ?";
    $actes = $sqlQuery->queryOne($sql, $siren);
    $message = '';
    $size = 0;
    $size = $actes["total"];
    echo "Taille totale des enveloppes actes : " . $size . " et nombre d'enveloppes " . $actes["nombre"] . "\n";

    return $size;
}

function compte_helios($authority_id, $sqlQuery)
{
    $sql = "select sum(file_size) as total ,count(*) as nombre FROM helios_transactions WHERE authority_id= ?";
    $helios = $sqlQuery->queryOne($sql, $authority_id);
    $message = '';
    $size = 0;
    $size = $helios["total"];
    echo "Taille totale des flux PES_ALLER : $size et le nombre de PES " . $helios["nombre"] . "\n";

    return $size;
}


$message = '';
$mail = EMAIL_ADMIN_TECHNIQUE;


$list_gpe = array('1','99','3','54','84','55','83','105','14','72','104','89','65','10','91','60','67','94','64','53','2','98','74','66','4','61','106','50');
$total = 0;
$units = 'BKMGTP';

function affiche_progression($total, $gpe, $authority_id, $units)
{
    echo $total . " --> $gpe --> " . $authority_id . "\n";
    $factor = (int)(log($total, 1000));
    $size = sprintf("%.2f", $total / pow(1000, $factor)) . @$units[$factor];
    echo $size . "\n";
}

foreach ($list_gpe as $gpe) {
    $actesAuthorities = $authoritySQL->getAllAdministeredForModuleBy(AdministeredModule::ACTES, (int)$gpe);
    foreach (array_keys($actesAuthorities) as $authority_id) {
        echo "coll id (actes) : " . $authority_id . "\n";
        $siren = $authoritySQL->getInfo($authority_id)['siren'];
        echo "$siren\n";
        $total += compte_actes($siren, $sqlQuery);
        affiche_progression($total, $gpe, $authority_id, $units);
    }

    $heliosAuthorities = $authoritySQL->getAllAdministeredForModuleBy(AdministeredModule::HELIOS, (int)$gpe);
    foreach (array_keys($heliosAuthorities) as $authority_id) {
        echo "coll id (helios) : " . $authority_id . "\n";
        $total += compte_helios($authority_id, $sqlQuery);
        affiche_progression($total, $gpe, $authority_id, $units);
    }
}
echo $total . "\n";
$factor = (int)(log($total, 1000));
$size = sprintf("%.2f", $total / pow(1000, $factor)) . @$units[$factor];
echo $size;
