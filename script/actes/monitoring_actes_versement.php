<?php

//Script charge de verifier l'etat des versements d'une collectivit� donnee
//Prend en parametre l'id de la collectivite

//RETOURNE 0 si tout va bien
//RETOURNE 2 si tout va mal
require_once( __DIR__."/../../init/init.php");

function creationurl($idcoll,$status){
    $URL=WEBSITE_SSL."/modules/actes/index.php?type=1&nature=&status=".$status."&num=&objet=&min_submission_date=&min_ack_date=&max_submission_date=&max_ack_date=&authority=".$idcoll."&count=100";
    return $URL;
}


function nbtransac($sqlquery,$idcoll,$status){
    $sql="SELECT count(*) ".
        "FROM actes_envelopes INNER JOIN actes_transactions ON actes_envelopes.id = actes_transactions.envelope_id ".
        "WHERE actes_transactions.last_status_id = '".$status."' ".
        "AND actes_transactions.type like '1' ".
        "AND authority_id = '".$idcoll."' ";
    //echo "$sql \n";

    $nb_transac=$sqlquery->queryOne($sql);
    return $nb_transac;
}

function pastellinfo($sqlquery,$idcoll){
	$sql = "SELECT pastell_url,pastell_id_e FROM authorities WHERE id = '".$idcoll."' ";
	return $sqlquery->query($sql);
}


if (empty($argv[1])){
    echo "Usage : {$argv[0]} authority_id\n";
    echo "\tControle des versement\n";
    exit;
}

$id_coll=$argv[1];

$email="s2low@libriciel.coop";
$subject="Les versements se sont bien passes";

$retour=0;
$sql="SELECT name FROM authorities WHERE id = '".$id_coll."'";
$namecoll=$sqlQuery->queryOne($sql);

$message="---------------------------------\n".
    "id : $id_coll \n".
    "nom de la collectivite : $namecoll\n";

// V�rification : il doit y avoir 0 actes en Erreur lors de l'archivage, statut 14
$last_status="14";
$nb_transac=nbtransac($sqlQuery,$id_coll,$last_status);

if ( $nb_transac > 0 ){
    $message .=  "- $nb_transac au statut Erreur lors de l'archivage. ---> ".creationurl($id_coll,$last_status)."\n";
}

// V�rification : il doit y avoir 0 actes en Erreur lors de l'envoie au SAE, statut 20
$last_status="20";
$nb_transac=nbtransac($sqlQuery,$id_coll,$last_status);

if ( $nb_transac > 0 ){
    $message .=  "- $nb_transac au statut Erreur lors de l'envoie au SAE. ---> ".creationurl($id_coll,$last_status)."\n";
}

// Indication : il doit y avoir des actes au statut Archive par le SAE, statut 13
$last_status="13";
$nb_transac=nbtransac($sqlQuery,$id_coll,$last_status);

if ( $nb_transac > 0 ){
    $message .=  "- $nb_transac au statut Archive par le SAE.\n";
}

// Indication : il peut y avoir des actes En attente de transmission au SAE, statut 19.
$last_status="19";
$nb_transac=nbtransac($sqlQuery,$id_coll,$last_status);

if ( $nb_transac > 0 ){
    $message .=  "- $nb_transac au statut En attente de transmission au SAE.\n";
}

// Indication : il peut y avoir des actes au statut Envoye au SAE, statut 12.
$last_status="12";
$nb_transac=nbtransac($sqlQuery,$id_coll,$last_status);

if ( $nb_transac > 0 ){
    $message .=  "- $nb_transac au statut Envoye au SAE. ---> ".creationurl($id_coll,$last_status)."\n";
}


$sql = "SELECT count(*) FROM actes_transactions AS at ".
	" JOIN actes_transactions_workflow AS atw ON (atw.transaction_id = at.id AND atw.status_id= 4) ".
	" WHERE at.authority_id=? AND at.type='1' ".
	" AND at.last_status_id IN (4,5) ".
	" AND atw.date > '2008-06-01' ".
	" AND atw.date < ? " ;

$nb_to_archive = $sqlQuery->queryOne(
	$sql,
	$id_coll,
	date("Y-m-d",strtotime("-".ActesPrepareSaeWorker::NB_DAYS_ARCHIVE_AFTER." days"))
);

if ($nb_to_archive > 0){
	$message .=  "- $nb_to_archive en retard pour l'envoi au SAE. \n";
}

$pastellPropertiesSQL = $objectInstancier->get(PastellPropertiesSQL::class);
$pastellProperties = $pastellPropertiesSQL->getPastellProperties($id_coll);
if ($pastellProperties->actes_send_auto){
    $message.= "Cette collecitivité est géré automatiquement\n";
}


$pastell=pastellinfo($sqlQuery,$id_coll);
//var_dump($pastell);
$pastellurl=explode('/api',$pastell[0]["pastell_url"]);
$message .= "Pastell ".$pastellurl[0]."/Document/index?id_e=".$pastell[0]["pastell_id_e"]."\n";

$message .= "\n";
echo $message;
