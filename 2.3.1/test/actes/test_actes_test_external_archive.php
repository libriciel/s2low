<?php 


// Configuration
require_once(dirname(__FILE__) . "/../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesClassification.class.php');

$env = new ActesEnvelope();

$err = $env->externalArchiveCheckFromFile("SLO-EACT--223400011--20100701-16.tar.gz");

if (!$err){
	echo " Erreur : " . $env->getErrorMsg();
} else {
	echo "ok";
}