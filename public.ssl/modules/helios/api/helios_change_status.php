<?php

require_once("../../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosRetour.class.php');

try{
	$module = new Module();
	if (!$module->initByName("helios")) {
	  $msg= "Erreur d'initialisation du module";
	  throw new Exception('KO'); 
	  
	}
	$me = new User();
	

	if (! $me->authenticate()) {
	  $msg= "Échec de l'authentification";
	  throw new Exception('KO'); 
	}
	
	if ($me->isGroupAdminOrSuper() || ! $module->isActive() || !$me->canEdit($module->get("name"))) {
	  $msg= "Accès refusé";
	 throw new Exception('KO'); 
	}
	
		$nomUSer = $me->get("name");
		$userId = $me->getId();
		$hr = new HeliosRetour();
		$doc = new DOMDocument();
	  $doc->formatOutput = true;	
	  $doc->preserveWhiteSpace = false;
	  $root=$doc->createElement("transaction");
		$doc->appendChild($root);
		
		$retour_id = Helpers :: getVarFromGet("id");
		$idElement=$doc->createElement("idRetour",$retour_id);
		$resultatElement=$doc->createElement("resultat");
		$messageElement=$doc->createElement("message");

		$root->appendChild($idElement);
		$root->appendChild($resultatElement);
		$root->appendChild($messageElement);

		if (!isset($retour_id) && empty($retour_id))
		{
			$msg="retour id est vide ou incorrecte.";
			throw new Exception('KO'); 
		}
		if (!$hr->changeStatus($retour_id, 1))
		{
			$msg="Changement de status est echoué.";
			throw new Exception('KO');
		} 
		$msg="reussi de changer status";
		$resultatElement->appendChild( $doc->createTextNode( "OK" ));
}
catch (Exception $e) {
    $resultatElement->appendChild( $doc->createTextNode( "KO" ));
}
	$messageElement->appendChild( $doc->createTextNode( $msg ));
	$xmlFile=HELIOS_FILES_ROOT."/temp/status.xml";
  $doc->save($xmlFile); 
if (!Helpers::sendFileToBrowser($xmlFile, basename($xmlFile), "text/xml")) {
	echo "error: impossible de envoyer ce xml "; 
}