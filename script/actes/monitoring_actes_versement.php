<?php

//Script charge de verifier l'etat des versements d'une collectivité donnée
//Prend en parametre l'id de la collectivite

use S2lowLegacy\Class\actes\ActesPrepareSaeWorker;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\PastellPropertiesSQL;

require_once(__DIR__ . "/../../init/init.php");
list($objectInstancier, $sqlQuery) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([ObjectInstancier::class, SQLQuery::class]);

function creationurl($idcoll, $status)
{
    $URL = \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/index.php?type=1&nature=&status=" . $status . "&num=&objet=&min_submission_date=&min_ack_date=&max_submission_date=&max_ack_date=&authority=" . $idcoll . "&count=100");
    return $URL;
}


function nbtransac($sqlquery, $idcoll, $status)
{
    $sql = "SELECT count(*) " .
        "FROM actes_envelopes INNER JOIN actes_transactions ON actes_envelopes.id = actes_transactions.envelope_id " .
        "WHERE actes_transactions.last_status_id = '" . $status . "' " .
        "AND actes_transactions.type like '1' " .
        "AND authority_id = '" . $idcoll . "' ";

    $nb_transac = $sqlquery->queryOne($sql);
    return $nb_transac;
}

function pastellinfo($sqlquery, $idcoll)
{
    $sql = "SELECT pastell_url,pastell_id_e FROM authorities WHERE id = '" . $idcoll . "' ";
    return $sqlquery->query($sql);
}


if (empty($argv[1])) {
    echo "Usage : {$argv[0]} authority_id\n";
    echo "\tControle des versement\n";
    exit;
}

$id_coll = $argv[1];

$sql = "SELECT name FROM authorities WHERE id = '" . $id_coll . "'";
$namecoll = $sqlQuery->queryOne($sql);

$message = "---------------------------------\n" .
    "id : $id_coll " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/authorities/admin_authority_sae.php?id=$id_coll\n") .
    "nom de la collectivite : $namecoll\n";

// Vérification : il doit y avoir 0 actes en Erreur lors de l'archivage, statut 14
$last_status = "14";
$nb_transac = nbtransac($sqlQuery, $id_coll, $last_status);

if ($nb_transac > 0) {
    $message .=  "- $nb_transac au statut Erreur lors de l'archivage. ---> " . creationurl($id_coll, $last_status) . "\n";
}

// Vérification : il doit y avoir 0 actes en Erreur lors de l'envoie au SAE, statut 20
$last_status = "20";
$nb_transac = nbtransac($sqlQuery, $id_coll, $last_status);

if ($nb_transac > 0) {
    $message .=  "- $nb_transac au statut Erreur lors de l'envoie au SAE. ---> " . creationurl($id_coll, $last_status) . "\n";
}

// Indication : il doit y avoir des actes au statut Archivé par le SAE, statut 13
$last_status = "13";
$nb_transac = nbtransac($sqlQuery, $id_coll, $last_status);

if ($nb_transac > 0) {
    $message .=  "- $nb_transac au statut Archive par le SAE.\n";
}

// Indication : il peut y avoir des actes En attente de transmission au SAE, statut 19. Fixer une limite de temps
$last_status = "19";
$nb_transac = nbtransac($sqlQuery, $id_coll, $last_status);

if ($nb_transac > 0) {
    $message .=  "- $nb_transac au statut En attente de transmission au SAE.\n";
}

// Indication : il peut y avoir des actes au statut Envoyé au SAE, statut 12. Fixer une limite de temps
$last_status = "12";
$nb_transac = nbtransac($sqlQuery, $id_coll, $last_status);

if ($nb_transac > 0) {
    $message .=  "- $nb_transac au statut Envoye au SAE. ---> " . creationurl($id_coll, $last_status) . "\n";
}


$sql = "SELECT count(*) FROM actes_transactions AS at " .
    " JOIN actes_transactions_workflow AS atw ON (atw.transaction_id = at.id AND atw.status_id= 4) " .
    " WHERE at.authority_id=? AND at.type='1' " .
    " AND at.last_status_id IN (4,5) " .
    " AND atw.date > '2008-06-01' " .
    " AND atw.date < ? " ;

$nb_to_archive = $sqlQuery->queryOne(
    $sql,
    $id_coll,
    date("Y-m-d", strtotime("-" . ActesPrepareSaeWorker::NB_DAYS_ARCHIVE_AFTER . " days"))
);


if ($nb_to_archive > 0) {
    $message .=  "- $nb_to_archive en retard pour l'envoi au SAE. \n";
}

$pastellPropertiesSQL = $objectInstancier->get(PastellPropertiesSQL::class);
$pastellProperties = $pastellPropertiesSQL->getPastellProperties($id_coll);
if ($pastellProperties->actes_send_auto) {
    $message .= "Cette collecitivité est gérée automatiquement\n";
}



$pastell = pastellinfo($sqlQuery, $id_coll);
$pastellurl = explode('/api', $pastell[0]["pastell_url"]);
$message .= "Pastell " . $pastellurl[0] . "/Document/index?id_e=" . $pastell[0]["pastell_id_e"] . "\n";
$message .= "\n";
echo $message;
