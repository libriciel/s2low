<?php
/**
 * \file actes_get_list.php
 * \brief Page de demande de statut d'une transaction
 * \author HTan
 * \date 16.12.2008
 * 
 *
 * Cette page renvoie la liste des transactions
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
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
	
	if ($me->isAdmin() || ! $module->isActive() || !$me->canEdit($module->get("name"))) {
	  $msg= "Accès refusé";
	  throw new Exception('KO'); 
	}

	$doc = new DOMDocument();
  $doc->formatOutput = true;	
  $doc->preserveWhiteSpace = false;
  $root=$doc->createElement("liste");
	$doc->appendChild($root);
	
	//le user siren = collectivite id.
	$idCollElement=$doc->createElement("idColl",$me->getUserSiren());
	$resultatElement=$doc->createElement("resultat");
	$messageElement=$doc->createElement("message");
	$dateDemandeElement=$doc->createElement("dateDemande", date("Y-m-d h:i:s"));
	$root->appendChild($idCollElement);
	$root->appendChild($resultatElement);
	$root->appendChild($messageElement);
	$root->appendChild($dateDemandeElement);

	$where = " WHERE status = 0 AND siren='" . $me->getUserSiren(). "' "; 
	$HR = new HeliosRetour();
	$envelops=$HR->getRetourList($where);
	foreach ($envelops as $envelope) {
		$pes_retourElement=$doc->createElement("pes_retour");
		$pes_retourElement->appendChild($doc->createElement("id",$envelope['id']));
		$pes_retourElement->appendChild($doc->createElement("nom",$envelope["filename"]));
		$pes_retourElement->appendChild($doc->createElement("date",$envelope["date"]));
		$root->appendChild($pes_retourElement);
	}
	$msg="liste réussi";
}
catch (Exception $e) {
    $resultatElement->appendChild( $doc->createTextNode( "KO" ));
}
	$messageElement->appendChild( $doc->createTextNode( $msg ));
	$xmlFile=HELIOS_FILES_ROOT."/temp/list.xml";
  $doc->save($xmlFile); 
if (!Helpers::sendFileToBrowser($xmlFile, basename($xmlFile), "text/xml")) {
	echo "error: impossible de envoyer ce xml "; 
}