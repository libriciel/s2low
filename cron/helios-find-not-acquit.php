#! /usr/bin/php
<?php

require_once( __DIR__ . "/../init/init.php");
ob_start();

$sql = "SELECT xml_nomfic,helios_transactions.submission_date,helios_ftp_dest FROM helios_transactions " .
		" JOIN authorities ON authorities.id=helios_transactions.authority_id " .
	" WHERE last_status_id=3 AND helios_transactions.submission_date < ?";

$today = date("Y-m-d");


$all = $sqlQuery->query($sql,$today);

if (! $all){
	$subject =  "Aucune transaction n'est resté en transmis";

} else {
	$subject = count($all) . " transactions sont resté à l'état transmis.";
}

$output = fopen("php://output","w");

foreach($all as $line){
	fputcsv($output, $line);

}
fclose($output);

$content = ob_get_contents();
ob_end_clean();

mail("eric.pommateau@adullact-projet.coop",$subject,$content);