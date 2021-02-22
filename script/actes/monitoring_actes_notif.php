<?php

//Script charge de verifier qu'il y a moins de X notification en attente d'etre envoyees

//RETOURNE 0 si tout va bien
//RETOURNE 2 si le nombre de transactions restant à notifier est supérieur à la limite
require_once( __DIR__."/../../init/init.php");

$email=EMAIL_ADMIN_TECHNIQUE;
$subject="Notification actes en attente";

$retour=0;
$message="OK";
$limit=30;

$sql = "SELECT actes_transactions.id  FROM actes_transactions ".
            " WHERE last_status_id IN ('-1','4','7','8','11','21')  AND auto_broadcasted=false AND type IN ('1','2','3','4','5','6')";

//echo "$sql \n";

$array_transac=$sqlQuery->queryOneCol($sql);

$nb_transac = count($array_transac);
//echo "$nb_transac \n";

if( $nb_transac > $limit){
        $message="CRITICAL";
        $retour=2;
        mail($email,$subject,"ATTENTION : $nb_transac transactions dont la notification n a pas encore ete envoyee La limite est a $limit transactions.");
}

echo "$message - $nb_transac transaction(s) dont la notification n est pas encore partie\n";
exit($retour);
