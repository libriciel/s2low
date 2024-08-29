<?php

//renvoi la liste des pes_retour reçus sous la forme d'un CSV

use S2lowLegacy\Lib\SQLQuery;

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);



$general_query = "SELECT hr.filename, hr.date, hr.siren, hr.file_size, hr.sha1" .
    " FROM helios_retour hr" .
    " ORDER BY hr.date DESC";

$output_handle = fopen("php://output", "w");


$head = array(
    "Date de reception",
    "Heure de recetpion",
    "Nom du fichier transmis",
    "Empreinte sha1",
    "Taille du fichier"
);

fputcsv($output_handle, $head);



foreach ($sqlQuery->query($general_query) as $row) {
    $date = $row['date'];
    $result = array();
    $result[] = date("Y-m-d", strtotime($date));
    $result[] = date("H:i:s", strtotime($date));
    $result[] = $row['filename'];
    $result[] = $row['sha1'];
    $result[] = $row['file_size'];
    fputcsv($output_handle, $result);
}

fclose($output_handle);
