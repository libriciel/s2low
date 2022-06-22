<?php

//Script charge de verifier que le plus vieil acte à l'état attente
//n'a pas plus de 30 minutes

//RETOURNE 0 si tout va bien
//RETOURNE 2 si le plus vieil acte à l'état posté à plus d'une heure
require_once( __DIR__."/../../init/init.php");

$email=EMAIL_ADMIN_TECHNIQUE;
$subject="Transaction actes a l etat en attente";

$retour=0;
$message="OK";
$last_status="2";
$interval="10";

$timestamp=time()-(30*60);

$sql_alert_Last_transmission= "SELECT NOW()-MAX(date) > INTERVAL '" . $interval . " minutes' FROM actes_transactions_workflow WHERE status_id =3";

$alert_last_transmission=$sqlQuery->queryOne($sql_alert_Last_transmission);

$sql="SELECT count(*) ".
    "FROM actes_envelopes INNER JOIN actes_transactions ON actes_envelopes.id = actes_transactions.envelope_id ".
    "WHERE actes_transactions.last_status_id = '".$last_status."' ".
    "AND DATE_TRUNC('minute',actes_envelopes.submission_date) < DATE_TRUNC('minute',TIMESTAMP '".date("Y-m-d H:i:s",$timestamp)."') ".
    "AND (actes_transactions.type like '1' OR actes_transactions.type like '6') "  ;

$nb_transac=$sqlQuery->queryOne($sql);

if(($nb_transac > 0) && $alert_last_transmission) {
    $message = "CRITICAL";
    $retour = 2;
    mail($email, $subject, "ATTENTION : Aucun message transmis depuis $interval minutes.\n $nb_transac transactions a etat en attente sur S2LOW depuis plus de 30 minutes.");
}

echo "$message - $nb_transac etat en attente de plus de 30 min\n";
exit($retour);
