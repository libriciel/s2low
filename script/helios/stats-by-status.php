<?php
//Script permettant d'avoir le nombre de transaction pour un statut donné
// Prend en paramètre un statut

// Sortie au format Influxdb
require_once( __DIR__."/../../init/init.php");

if($argc < 2){
        echo "il manque le statut a rechercher";
        exit(-2);
}

$status_id=(int) $argv[1];

if(!strval($status_id) == $argv[1]){
  echo "Le statut doit etre un entier";
  exit(-2);
}


$heliosTransactionsSQL = $objectInstancier->get(HeliosTransactionsSQL::class);
$nb_transac=$heliosTransactionsSQL->getNbByStatus($status_id);
echo "helios_transactions,status=$status_id,application=s2low nb_transac=$nb_transac";
