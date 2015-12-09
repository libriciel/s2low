<?php

//renvoi la liste des actes transmis sous la forme d'un CSV

require_once( __DIR__."/../../init/init.php");


$sql = "SELECT DISTINCT ae.id, ae.file_path, atw.date, auth.siren, auth.department, auth.district";
$sql .= " FROM actes_envelopes ae";
$sql .= " LEFT JOIN users ON ae.user_id=users.id";
$sql .= " LEFT JOIN authorities auth ON users.authority_id=auth.id";
$sql .= " LEFT JOIN actes_transactions at ON at.envelope_id=ae.id";
$sql .= " LEFT JOIN actes_transactions_workflow atw ON atw.transaction_id=at.id";
// On veut récupérer la date où la transaction a été transmise => statut 3
$sql .= " WHERE atw.status_id=3";
$sql .= " ORDER BY atw.date DESC";



$pdo = $sqlQuery->getPdo();

$pdoStatement = $sqlQuery->getPdo()->prepare($sql);
$pdoStatement->execute();

$head = array(
	"Date de transmission",
	"Heure de transmission",
	"Nom du fichier (.tar.gz.) transmis",
	"Nom des fichiers contenus dans le fichier .tar.gz. transmis",
	"SIREN de la collectivité émettrice",
	"Département de la collectivité",
	"Arrondissement de la collectivité"
);

$sql ="SELECT filename FROM actes_included_files WHERE envelope_id=?";

$output_handle = fopen("php://output","w");
fputcsv($output_handle,$head);



while ($row = $pdoStatement->fetch(PDO::FETCH_ASSOC) ){
	$date = $row['date'];

	$all_files = $sqlQuery->queryOneCol($sql,$row['id']);

	$result = array();
	$result[] = date("Y-m-d",strtotime($date));
	$result[] = date("H:i:s",strtotime($date));
	$result[] = basename($row['file_path']);
	$result[] = implode("|",$all_files);
	$result[] = $row['siren'];
	$result[] = $row['department'];
	$result[] = $row['district'];
	fputcsv($output_handle,$result);
}
fclose($output_handle);
