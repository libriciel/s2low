<?php

require_once( __DIR__."/../../init/init.php");


$sql = "select authority_groups.name,authority_group_id as id FROM users " .
	" JOIN authority_groups ON users.authority_group_id=authority_groups.id " .
	" WHERE users.email='XXXXX'";

$result = $sqlQuery->query($sql);


$sql_helios = "SELECT sum(file_size) FROM helios_transactions " .
	" WHERE authority_id=?";

$sql_actes = "SELECT sum(file_size) FROM actes_envelopes JOIN actes_transactions ON actes_transactions.envelope_id=actes_envelopes.id ".
	" WHERE actes_transactions.authority_id=?";

$total_helios = 0;
$total_actes = 0;

foreach($result as $group_info){
	$group_helios_size = 0;
	$group_actes_size = 0;
	echo "\nGROUPE {$group_info['name']} : \n";
	$sql = "SELECT name,id FROM authorities WHERE authority_group_id=?";
	$authority_list = $sqlQuery->query($sql,$group_info['id']);
	foreach($authority_list as $authority_info){
		echo "\tCOLLECTIVITE {$authority_info['name']}\n";
		$helios_size = $sqlQuery->queryOne($sql_helios,$authority_info['id']);
		echo "\t\tHelios: $helios_size\n";
		$group_helios_size += $helios_size;
		$actes_size = $sqlQuery->queryOne($sql_actes,$authority_info['id']);
		echo "\t\tActes: $actes_size\n";
		$group_actes_size += $actes_size;
	}
	echo "\tgroupe(helios) : $group_helios_size\n";
	echo "\tgroupe(actes) : $group_actes_size\n";
	$total_helios += $group_helios_size;
	$total_actes += $group_actes_size;
}

echo "TOTAL (helios) : $total_helios\n";
echo "TOTAL (actes) : $total_actes\n";