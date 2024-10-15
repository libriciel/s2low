<?php

//renvoi la liste des actes transmis sous la forme d'un CSV

use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\SQLQuery;

require_once(__DIR__ . '/../../init/init.php');
$sqlQuery = LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);



$general_query = 'select DISTINCT helios_transactions.id, filename, sha1, file_size, date, authorities.siren, authorities.department, authorities.district, authority_types.description as type_authority FROM helios_transactions ' .
    ' JOIN helios_transactions_workflow ON helios_transactions.id=helios_transactions_workflow.transaction_id ' .
    ' JOIN authorities ON helios_transactions.authority_id = authorities.id ' .
    ' JOIN authority_types ON authorities.authority_type_id = authority_types.id' .
    ' WHERE helios_transactions_workflow.date > ? ' .
    ' AND helios_transactions_workflow.date < ? ' .
    ' AND helios_transactions_workflow.status_id = 3' .
    ' ORDER BY date ';

$sql = 'SELECT min(date) FROM helios_transactions_workflow';
$min_date = $sqlQuery->queryOne($sql);

$sql = 'SELECT max(date) FROM helios_transactions_workflow';
$max_date = $sqlQuery->queryOne($sql);

$start = new DateTime($min_date);
$end = new DateTime($max_date);
$end->modify('next day');
$interval = DateInterval::createFromDateString('1 day');
$datePeriod =  new DatePeriod($start, $interval, $end);


$output_handle = fopen('php://output', 'w');


$head = [
    'Date de transmission',
    'Nom du fichier transmis',
    'Empreinte SHA1',
    'Taille du fichier (octets)',
    'SIREN de la collectivité émettrice',
    'Département de la collectivité',
    'Arrondissement de la collectivité',
    'Type de la collectivité'
];

fputcsv($output_handle, $head);


foreach ($datePeriod as $dt) {
    /**@var DateTime $dt */
    $date1 = $dt->format('Y-m-d');
    $dt->modify('next day');
    $date2 = $dt->format('Y-m-d');

    foreach ($sqlQuery->query($general_query, $date1, $date2) as $row) {
            $date = $row['date'];
            $result = [];
            $result[] = date('c', strtotime($date));
            $result[] = $row['filename'];
            $result[] = $row['sha1'];
            $result[] = $row['file_size'];
            $result[] = $row['siren'];
            $result[] = $row['department'];
            $result[] = $row['district'];
            $result[] = $row['type_authority'];
            fputcsv($output_handle, $result);
    }
}
fclose($output_handle);
