<?php
declare(ticks = 1);



require_once(dirname(__FILE__)."/../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT."/public.ssl/modules/actes/class/ActesClassificationCreation.class.php");



if( (date("d") <= '07') && (date("l") == 'Saturday')){

	$authorities_up_to_date = getAuthoritiesUptodate();

	$classificationCreation = new ActesClassificationCreation();
	
	if (! ACTES_RESTRICT_CLASSIF_REQUEST_FREQUENCY){
		$classificationCreation->unsetFrequencyRestriction();
	}
	
	
	$authority = new Authority();
	$authorities = $authority->getAllAuthorities();
	

	/** @var Authority $authority */
	foreach ($authorities as $authority){
		echo $authority['name'] . ":";
		if (! $authority['siren']){
			echo "[PASS]\n";
			continue;
		}
		//Ca c'est pour remettre à jour toutes les collectivités à partir d'une certaine date car il y a une fuite mémoire...
		/*if (in_array($authority['id'],$authorities_up_to_date)){
			echo "déjà fait....\n";
			continue;
		}*/

		$result = $classificationCreation->createEnveloppe(new Authority($authority['id']));
		if ($result){
			echo "[OK]\n";
		} else {
			echo "[FAIL] - " . $classificationCreation->getLastMessage()."\n";
		}
		
	}
}


function getAuthoritiesUptodate(){
	$sql = "SELECT authority_id FROM actes_transactions " .
		" LEFT JOIN actes_transactions_workflow ON actes_transactions.id=actes_transactions_workflow.transaction_id ".
		" WHERE \"type\"='7' AND status_id=1 AND date>'2017-04-19 12:00:00'";
	global $sqlQuery;
	return $sqlQuery->queryOneCol($sql);
}