<?php 
require_once(dirname(__FILE__)."/../../class/Parapheur.class.php");
require_once(dirname(__FILE__)."/../../class/EDDOS.class.php");

$data = "Données a signer";

$dataFileName = tempnam('/tmp', 'paraph_');
$dataFile = fopen($dataFileName,"w");
fwrite($dataFile,$data);
fclose($dataFile);

$signFileName = tempnam('/tmp', 'sign_');
		
/* Test du nouveau parapheur PHP */
$parapheur = new Parapheur($data);

$signature = $parapheur->getSignature();

$parapheur = new Parapheur($data);


if (! $parapheur->verify($signature)){
	echo "Problème sur la vérification parapheur ";
	echo $parapheur->getLastError();
}

$parapheur = new Parapheur("aautre données");
if ($parapheur->verify($signature)){
	echo "Problème sur la vérification parapheur";
	echo $parapheur->getLastError();
}

/* Compatibilité avec la brique EDDOS */

EDDOS::callEDDOSTimestamp($dataFileName,$signFileName);
$r = EDDOS::callEDDOSCheckSign($dataFileName,$signFileName);
if (!$r){
	echo "Problème sur EDDOS";
}
$eddosSign = file_get_contents($signFileName);

/** EDDOS signe, parapheur vérifie **/
$parapheur = new Parapheur($data);
if (! $parapheur->verify($signature)){
	echo "Problème sur la vérification parapheur sur une signature EDDOS ";
	echo $parapheur->getLastError();
}

/** Parapheur signe, EDDOS vérifie**/
$parapheur = new Parapheur($data);
$signature = $parapheur->getSignature();
file_put_contents($signFileName,$signature);
$r = EDDOS::callEDDOSCheckSign($dataFileName,$signFileName);
if (!$r){
	echo "Problème sur vérification EDDOS sur une signature parapheur";
}



unlink($dataFileName);
unlink($signFileName);