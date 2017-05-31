<?php

//Script charge de verifier que le plus vieil acte à l'état attente
//n'a pas plus de 30 minutes

//RETOURNE 0 si tout va bien
//RETOURNE 2 si le plus vieil acte à l'état posté à plus d'une heure
require_once( __DIR__."/../../init/init.php");

$email="s2low@libriciel.coop";
$subject="Transaction actes a l etat en attente";

$retour=0;
$message="OK";
$limit=10;
$last_status="2";
$timestamp=time()-(30*60);
$sql="SELECT count(*) ".
     "FROM actes_envelopes INNER JOIN actes_transactions ON actes_envelopes.id = actes_transactions.envelope_id ".
     "WHERE actes_transactions.last_status_id = '".$last_status."' ".
     "AND DATE_TRUNC('minute',actes_envelopes.submission_date) < DATE_TRUNC('minute',TIMESTAMP '".date("Y-m-d H:i:s",$timestamp)."') ".
     "AND (actes_transactions.type like '1' OR actes_transactions.type like '6') "  ;

#echo "$sql \n";

$nb_transac=$sqlQuery->queryOne($sql);

#echo "$nb_transac \n";

if( $nb_transac > $limit){
        $message="CRITICAL";
	$retour=2;
        mail($email,$subject,"ATTENTION : $nb_transac transactions a etat en attente sur S2LOW depuis plus de 30 minutes. La limite est a $limit actes a etat en attente.");
}

echo "$message - $nb_transac etat en attente de plus de 30 min\n";
exit($retour);
