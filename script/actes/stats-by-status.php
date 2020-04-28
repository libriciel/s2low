<?php

//Script appelé pour avoir le nombre d'enveloppes de type 1 ou 6 pour un statut donné en paramètre
// le script doit avoir un paramètre obligaoire, le statut
// le second est optionnel, il s'agit d'une durée en minutes
// Cela permet d'avoir le nombre d'enveloppes entre x minutes et maitenant

// la sortie est au format Influxdb

require_once( __DIR__."/../../init/init.php");
$actesStatuts = $objectInstancier->get(ActesStatusSQL::class)->getAllStatus();

if($argc < 2){
        echo "il manque le statut des enveloppes";
        exit(-2);
}

$status_id=(int) $argv[1];

if(!array_key_exists($status_id,$actesStatuts)){
    echo "le statut doit etre un statut connu";
    exit(-2);
}

$retention=null;
if($argc == 3){
   $retention=(int) $argv[2];
   if(!strval($retention) == $argv[2]){
     echo "La retention doit etre exprimee en minutes";
     exit(-2);
   }
}

$sql="SELECT count(actes_envelopes.id) ".
    "FROM actes_envelopes INNER JOIN actes_transactions ON actes_envelopes.id = actes_transactions.envelope_id ".
    "WHERE actes_transactions.last_status_id = '".$status_id."' ".
    "AND (actes_transactions.type = '1' OR actes_transactions.type = '6') "  ;

if($retention){
    $timestamp=time();
    $timestampmax=time()-($retention*60);
    $sql = $sql." AND actes_envelopes.submission_date < '".date("Y-m-d H:i:s",$timestamp)."' ".
        "AND actes_envelopes.submission_date > '".date("Y-m-d H:i:s",$timestampmax)."' ";

	$nb_transac=$sqlQuery->queryOne($sql);
	echo "actes_enveloppes_limit,status=$status_id,application=s2low nb_enveloppes=$nb_transac";
	exit;
}

$nb_transac=$sqlQuery->queryOne($sql);

echo "actes_enveloppes,status=$status_id,application=s2low nb_enveloppes=$nb_transac";
