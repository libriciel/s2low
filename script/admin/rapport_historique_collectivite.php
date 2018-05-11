<?php

require_once( __DIR__."/../../init/init.php");

// Paramètre attendu id coll
// Retourne la taille du dossier actes et la taille des flux PES
if (empty($argv[1])){
    echo "Usage : {$argv[0]} authority_id\n";
    exit;
}
$authority_id=$argv[1];

$mail=EMAIL_ADMIN_TECHNIQUE;

$sql="SELECT name FROM authorities WHERE id = ?";
$namecoll=$sqlQuery->queryOne($sql,$authority_id);


$sql="SELECT siren FROM authorities WHERE id= ?";
$siren=$sqlQuery->queryOne($sql,$authority_id);
echo "$siren\n";

$message="---------------------------------\n".
    "id : $authority_id \n".
    "nom de la collectivite : $namecoll\n";
// ACTES

$sql="SELECT sum(file_size),count(*) FROM actes_envelopes WHERE siren like ?";
$actes=$sqlQuery->query($sql,$siren);
//var_dump($actes);exit;
if($actes[0]["sum"] > 0){
    $factor = (int)(log($actes[0]["sum"], 1000));
    $units = 'BKMGTP';
    $sizeactes = sprintf("%.2f", $actes[0]["sum"] / pow(1000, $factor)) . @$units[$factor];
    
    echo "taille actes : ".$sizeactes." et nombre d'enveloppes : ".$actes[0]["count"]."\n";
    $message .= "Taille totale des enveloppes actes : ".$sizeactes." et nombre d'enveloppes ".$actes[0]["count"]."\n";
}else{
    $message .= "Il n'y a pas d'actes\n";
}

// HELIOS
$sql="select sum(file_size),count(*) FROM helios_transactions WHERE authority_id= ?";
$helios=$sqlQuery->query($sql,$authority_id);
//var_dump($helios);
if($helios[0]["sum"] > 0){
    $factor = (int)(log($helios[0]["sum"], 1000));
    $units = 'BKMGTP';
    $sizehelios = sprintf("%.2f", $helios[0]["sum"] / pow(1000, $factor)) . @$units[$factor];
    
    echo "taille helios : ".$sizehelios." et nombre de fichiers : ".$helios[0]["count"]."\n";
    
    $message .= "Taille totale des flux PES_ALLER : $sizehelios et le nombre de PES ".$helios[0]["count"];
}else{
    $message .= "Il n'y a pas de PES_ALLER\n";
}

mail($mail,"Rapport taille historique collectivite $namecoll",$message);
