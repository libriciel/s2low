<?php 
exit;
//Correction du bug survenu le 29/06/2009 
//La plateforme n'a pas convenablement horodaté l'ensemble des changement d'état
//08h32min22s à 18h16min27s


require_once (dirname(__FILE__)."/../config/config.php");
require_once (SITEROOT . '/class/include.class.php');

$db = DatabasePool::getInstance();

$sql = "SELECT actes_transactions_workflow.*, " . 
		" actes_status.name as status_name, actes_status.id as status_id, " .
		" actes_envelopes.user_id " .
		" FROM actes_transactions_workflow " .
		" JOIN actes_status ON actes_transactions_workflow.status_id = actes_status.id".
		" JOIN actes_transactions ON actes_transactions_workflow.transaction_id = actes_transactions.id " .
		" JOIN actes_envelopes ON  actes_transactions.envelope_id=actes_envelopes.id "  . 
		" WHERE DATE(date)='2010-06-29' AND actes_transactions_workflow.status_id != 1";

$result = $db->select($sql);

while ($row = $result->get_next_row()) {
	$msg = "L'archive : " . $row['transaction_id'] . " passe a l'etat " . getStatusName($row['status_id'],$row['status_name']);
	if ($row['status_id'] == 4){
		 $msg = "L'acte : " . $row['transaction_id']  . " passe en recu.";
	}
	echo $msg . " - ".$row['user_id'];
	
	$sql = "SELECT count(*) as count FROM logs WHERE message='".addslashes($msg)."'";
	$r2 = $db->select($sql);
	$cpt = $r2->get_next_row();
	if ($cpt['count']) {
		echo " -- Déjà dans la base";
	} else {
		Log::newEntry('TdT', $msg, 1, $row['date'], 'USER', 'actes', false , $row['user_id']);
		echo " -- Ajouté dans la base";
	}	
	echo "\n";	
}

function getStatusName($status_id,$status_name){
	switch ($status_id){
		case 2: return "en attente";
		case 3: return "transmis";
	}
	
	return $status_name;
}

function horodate($msg,$id_u){
	$sql = "INSERT INTO logs(date,severity,module,issuer,user_id,visibilite,message,timestamp) VALUES ()";
}
