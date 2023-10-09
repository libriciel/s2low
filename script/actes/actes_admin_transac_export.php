<?php

//renvoi la liste des actes transmis sous la forme d'un CSV

use S2lowLegacy\Lib\SQLQuery;

require_once(__DIR__ . "/../../init/init.php");
list($sqlQuery) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([SQLQuery::class]);



$general_query = "select DISTINCT actes_envelopes.id, actes_envelopes.file_path, actes_transactions_workflow.date, authorities.siren, authorities.department, authorities.district FROM actes_transactions " .
    " JOIN actes_transactions_workflow ON actes_transactions.id=actes_transactions_workflow.transaction_id " .
    " JOIN actes_envelopes ON actes_transactions.envelope_id = actes_envelopes.id" .
    " JOIN authorities ON actes_transactions.authority_id = authorities.id " .
    " WHERE actes_transactions_workflow.date > ? " .
    " AND actes_transactions_workflow.date < ? " .
    " AND actes_transactions_workflow.status_id = 3" .
    " ORDER BY date ";

$filename_query = "SELECT filename FROM actes_included_files WHERE envelope_id=?";


$sql = "SELECT min(date) FROM actes_transactions_workflow";
$min_date = $sqlQuery->queryOne($sql);

$sql = "SELECT max(date) FROM actes_transactions_workflow";
$max_date = $sqlQuery->queryOne($sql);

$start = new DateTime($min_date);
$end = new DateTime($max_date);
$end->modify("next day");
$interval = DateInterval::createFromDateString('1 day');
$datePeriod =  new DatePeriod($start, $interval, $end);


$output_handle = fopen("php://output", "w");

$head = array(
    "Date de transmission",
    "Heure de transmission",
    "Nom du fichier (.tar.gz.) transmis",
    "Nom des fichiers contenus dans le fichier .tar.gz. transmis",
    "SIREN de la collectivité émettrice",
    "Département de la collectivité",
    "Arrondissement de la collectivité"
);

fputcsv($output_handle, $head);


foreach ($datePeriod as $dt) {
    /**@var $dt DateTime */
    $date1 = $dt->format("Y-m-d");
    $dt->modify("next day");
    $date2 = $dt->format("Y-m-d");

    foreach ($sqlQuery->query($general_query, $date1, $date2) as $row) {
            $date = $row['date'];
            $all_files = $sqlQuery->queryOneCol($filename_query, $row['id']);
            $result = array();
            $result[] = date("Y-m-d", strtotime($date));
            $result[] = date("H:i:s", strtotime($date));
            $result[] = basename($row['file_path']);
            $result[] = implode("|", $all_files);
            $result[] = $row['siren'];
            $result[] = $row['department'];
            $result[] = $row['district'];
            fputcsv($output_handle, $result);
    }
}
fclose($output_handle);
