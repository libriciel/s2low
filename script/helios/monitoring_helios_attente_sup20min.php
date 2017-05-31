<?php

//Script charge de verifier que le plus vieil acte à l'état en attente
//n'a pas plus de 20 minutes

//RETOURNE 0 si tout va bien
//RETOURNE 2 si tout va mal
require_once( __DIR__."/../../init/init.php");

$email="s2low@libriciel.coop";
$subject="Transaction HELIOS a l etat attente";

$retour=0;
$message="OK";
$limit=10000;
$last_status="2";
$timestamp=time()-(20*60);
$sql="SELECT count(*) ".
     "FROM helios_transactions ".
     "WHERE helios_transactions.last_status_id = '".$last_status."' ".
     "AND DATE_TRUNC('minute',helios_transactions.submission_date) < DATE_TRUNC('minute',TIMESTAMP '".date("Y-m-d H:i:s",$timestamp)."')";

#echo "$sql \n";

$nb_transac=$sqlQuery->queryOne($sql);

#echo "$nb_transac \n";

if( $nb_transac > $limit){
        $message="CRITICAL";
        $retour=2;
        mail($email,$subject,"ATTENTION : $nb_transac transactions a etat en attente sur S2LOW depuis plus de 20 minutes. La limite est a $limit helios a etat en attente.");
}

echo "$message - $nb_transac etat en attente de plus de 20 min\n";
exit($retour);
