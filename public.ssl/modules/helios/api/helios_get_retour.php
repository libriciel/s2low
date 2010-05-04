<?php

/**
 * \file actes_get_retour.php
 * \brief page de recuperer le ficher de PES_RETOUR
 * \author HTan
 * \date 16/12/2008
 * 
 *
 * Cette page permet de télécharger un fichier  à partir de l'id de sa transaction
 * 
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once ("../../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosRetour.class.php');

		$retourId= Helpers :: getVarFromGet("id");
	  $doc = new DOMDocument();
	  $doc->formatOutput = true;	
	  $doc->preserveWhiteSpace = false;
	  $root=$doc->createElement("get_retour");
		$doc->appendChild($root);
		

		$idElement=$doc->createElement("id",$retourId);
		$resultatElement=$doc->createElement("resultat");
		$messageElement=$doc->createElement("message");
		
		$root->appendChild($idElement);
		$root->appendChild($resultatElement);
		$root->appendChild($messageElement);
try{
	$module = new Module();
	if (!$module->initByName("helios")) {
	  $msg = "Erreur d'initialisation du module";
	  throw new Exception('KO'); 
	}
	
	$me = new User();
	
	if (!$me->authenticate()) {
	  $msg = "Échec de l'authentification";
	  throw new Exception('KO');
	}
	
	if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
	  $msg= "Accès refusé";
	  throw new Exception('KO');
	}

	$entity = new HeliosRetour($retourId);
	$entity->init();
	$filename=$entity->get("filename");
	
	if(!$filename)
	{
		$msg = "retour id n'est pas correcte";
	  throw new Exception('KO');
	}
	if (!$entity->sendfile($filename)) {
	  $msg = "Erreur d'envoi du fichier " . HELIOS_RESPONSES_ROOT.$filename . " : " . $entity->getErrorMsg();
	  throw new Exception('KO');
	}
}
catch(Exception $e){
	$resultatElement->appendChild( $doc->createTextNode( "KO" ));
  $messageElement->appendChild( $doc->createTextNode( $msg));	
  $xmlFile=HELIOS_FILES_ROOT."/temp/retour.xml";
  $doc->save($xmlFile); 
	if (!Helpers::sendFileToBrowser($xmlFile, basename($xmlFile), "text/xml")) {
		echo "error: impossible de envoyer ce xml "; 
	}
}
 