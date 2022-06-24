<?php

//Script charge de verifier que le nombre d'acte revenu en erreur ne dépasse pas un seuil
//sur une tranche horaire

//RETOURNE 0 si tout va bien
//RETOURNE 2 si tout va mal
require_once(__DIR__ . "/../../init/init.php");

$email = EMAIL_ADMIN_TECHNIQUE;
$subject = "Transaction actes a l etat erreur";

$retour = 0;
$message = "OK";
$limit = 75;
$last_status = "-1";
$timestamp = time();
$timestampmax = time() - (2 * 60 * 60);
$sql = "SELECT count(actes_envelopes.id) " .
     "FROM actes_envelopes INNER JOIN actes_transactions ON actes_envelopes.id = actes_transactions.envelope_id " .
     "WHERE actes_transactions.last_status_id = '" . $last_status . "' " .
    "AND actes_envelopes.submission_date < '" . date("Y-m-d H:i:s", $timestamp) . "' " .
    "AND actes_envelopes.submission_date > '" . date("Y-m-d H:i:s", $timestampmax) . "' " .
    "AND (actes_transactions.type = '1' OR actes_transactions.type = '6') "  ;

#echo "$sql \n";

$nb_transac = $sqlQuery->queryOne($sql);

#echo "$nb_transac \n";

if ($nb_transac > $limit) {
        $message = "CRITICAL";
        $retour = 2;
        mail($email, $subject, "ATTENTION : $nb_transac transactions sont en erreur sur S2LOW sur une periode de 2h. La limite est a $limit actes en erreur.");
}

echo "$message - $nb_transac en erreur sur une periode de 2h\n";
exit($retour);
