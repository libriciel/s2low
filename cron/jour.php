<?php 

//Ce script est trèss gourmand en mémoire s'il y a beaucoup de collectivité à  traiter.
ini_set("memory_limit","100M");


require_once(dirname(__FILE__)."/../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT."/public.ssl/modules/actes/class/ActesClassificationCreation.class.php");

$classificationCreation = new ActesClassificationCreation();

if (! ACTES_RESTRICT_CLASSIF_REQUEST_FREQUENCY){
	$classificationCreation->unsetFrequencyRestriction();
}


$authority = new Authority();
$authorities = $authority->getAllAuthorities();


foreach ($authorities as $authority){
	echo $authority['name'] . ":";
	if (! $authority['siren']){
		echo "[PASS]\n";
		continue;
	}

	$result = $classificationCreation->createEnveloppe(new Authority($authority['id']));
	if ($result){
		echo "[OK]\n";
	} else {
		echo "[FAIL] - " . $classificationCreation->getLastMessage()."\n";
	}
	
}
