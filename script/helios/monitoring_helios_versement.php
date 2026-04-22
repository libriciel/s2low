<?php

//Script charge de verifier l'etat des versements d'une collectivité donnee
//Prend en parametre l'id de la collectivite

//RETOURNE 0 si tout va bien
//RETOURNE 2 si tout va mal
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Lib\SQLQuery;

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);

function creationurl($idcoll, $status)
{
    $URL = \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/helios/index.php?status=$status&num=&min_submission_date=&min_ack_date=&max_submission_date=&max_ack_date=&authority=$idcoll&nomFic=");
    return $URL;
}


function nbtransac($sqlquery, $idcoll, $status)
{
    $sql = "SELECT count(*) " .
        "FROM helios_transactions " .
        "WHERE helios_transactions.last_status_id = '" . $status . "' " .
        "AND authority_id = '" . $idcoll . "' ";
    //echo "$sql \n";

    $nb_transac = $sqlquery->queryOne($sql);
    return $nb_transac;
}

if (empty($argv[1])) {
    echo "Usage : {$argv[0]} authority_id\n";
    echo "\tControle des versement\n";
    exit;
}

$id_coll = $argv[1];

$retour = 0;
$sql = "SELECT name FROM authorities WHERE id = '" . $id_coll . "'";
$namecoll = $sqlQuery->queryOne($sql);

$message = "---------------------------------\n" .
    "id : $id_coll \n" .
    "nom de la collectivite : $namecoll\n";

// Vérification : il doit y avoir 0 actes en Erreur lors de l'envoi au SAE, statut 20
$last_status = "20";
$nb_transac = nbtransac($sqlQuery, $id_coll, $last_status);

if ($nb_transac > 0) {
    $message .=  "- $nb_transac au statut Erreur lors de l'envoi au SAE. ---> " . creationurl($id_coll, $last_status) . "\n";
}

// Vérification : il doit y avoir 0 actes Refuser par le SAE, statut 11
$last_status = "11";
$nb_transac = nbtransac($sqlQuery, $id_coll, $last_status);

if ($nb_transac > 0) {
    $message .=  "- $nb_transac au statut Refuser par le SAE. ---> " . creationurl($id_coll, $last_status) . "\n";
}

// Indication : il doit y avoir des actes au statut Accepte par le SAE, statut 10
$last_status = "10";
$nb_transac = nbtransac($sqlQuery, $id_coll, $last_status);

if ($nb_transac > 0) {
    $message .=  "- $nb_transac au statut Accepte par le SAE.\n";
}

// Indication : il peut y avoir des actes En attente de transmission au SAE, statut 19. Fixer une limite de temps
$last_status = "19";
$nb_transac = nbtransac($sqlQuery, $id_coll, $last_status);

if ($nb_transac > 0) {
    $message .=  "- $nb_transac au statut En attente de transmission au SAE.\n";
}

// Indication : il peut y avoir des actes au statut Envoyé au SAE, statut 12. Fixer une limite de temps
$last_status = "9";
$nb_transac = nbtransac($sqlQuery, $id_coll, $last_status);

if ($nb_transac > 0) {
    $message .=  "- $nb_transac au statut Envoye au SAE. ---> " . creationurl($id_coll, $last_status) . "\n";
}

$message .= "\n";
echo $message;
