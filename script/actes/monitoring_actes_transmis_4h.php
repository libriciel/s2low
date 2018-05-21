<?php

//Script charge de verifier que le plus vieil acte à l'état transmis
//n'a pas plus de 4h

//RETOURNE 0 si tout va bien
//RETOURNE 2 si le plus vieil acte à l'état posté à plus d'une heure
require_once( __DIR__."/../../init/init.php");

$email="s2low@libriciel.coop";
$subject="Transaction actes a l etat transmis";

$retour=0;
$message="OK";
$limit=50;
$last_status="3";
$timestamp=time()-(60*60);
$timestampmax=time()-(4*60*60);
$sql="SELECT count(actes_envelopes.id) ".
     "FROM actes_envelopes INNER JOIN actes_transactions ON actes_envelopes.id = actes_transactions.envelope_id ".
     "WHERE actes_transactions.last_status_id = '".$last_status."' ".
     "AND actes_envelopes.submission_date < '".date("Y-m-d H:i:s",$timestamp)."' ".
     "AND actes_envelopes.submission_date > '".date("Y-m-d H:i:s",$timestampmax)."' ".
     "AND (actes_transactions.type = '1' OR actes_transactions.type = '6') "  ;

#echo "$sql \n";

$nb_transac=$sqlQuery->queryOne($sql);

#echo "$nb_transac \n";

if( $nb_transac > $limit){
        $message="CRITICAL";
        $retour=2;
        mail($email,$subject,"ATTENTION : $nb_transac transactions a etat transmis sur S2LOW depuis ".date("Y-m-d H:i:s",$timestampmax).". La limite est a $limit actes a etat transmis.");
}

echo "$message - $nb_transac etat transmis depuis ".date("Y-m-d H:i:s",$timestampmax)."\n";
exit($retour);
