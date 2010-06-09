<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : C. Pop Mars 2007
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à la
 * dématèrialisation de l'administration. 
 *
 * Ce logiciel est régi par la licence CeCILL soumise au droit français et
 * respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusée par le CEA, le CNRS et l'INRIA 
 * sur le site "http://www.cecill.info".
 *
 * En contrepartie de l'accessibilité au code source et des droits de copie,
 * de modification et de redistribution accordés par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitée.  Pour les mêmes raisons,
 * seule une responsabilité restreinte pèse sur l'auteur du programme,  le
 * titulaire des droits patrimoniaux et les concédants successifs.
 *
 * A cet égard  l'attention de l'utilisateur est attirée sur les risques
 * associés au chargement,  à l'utilisation,  à la modification et/ou au
 * développement et à la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à 
 * manipuler et qui le réserve donc à des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à charger  et  tester  l'adéquation  du
 * logiciel à leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * à l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder à cet en-tête signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/
?>
<?php
/**
 * \file actes_transac_get_status.php
 * \brief Page de demande de statut d'une transaction
 * \author HTan
 * \date 16.12.2008
 * 
 *
 * Cette page renvoie le statut d'une transaction
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');

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
	
		$doc = new DOMDocument();
	  $doc->formatOutput = true;	
	  $doc->preserveWhiteSpace = false;
	  $root=$doc->createElement("transaction");
		$doc->appendChild($root);
		$idElement=$doc->createElement("id");
		$resultatElement=$doc->createElement("resultat");
		$messageElement=$doc->createElement("message");
		$statusElement=$doc->createElement("status");
		$root->appendChild($idElement);
		$root->appendChild($resultatElement);
		$root->appendChild($messageElement);
		$root->appendChild($statusElement);
	// Recuperation des variables du GET
	$transId = Helpers::getVarFromGet("transaction");
	$idElement->appendChild( $doc->createTextNode( $transId ));
	
	$zeTrans = new HeliosTransaction();
	
	if (isset($transId) && ! empty($transId)) {
	  $zeTrans->setId($transId);
	  if ($zeTrans->init()) {
		$owner = new User($zeTrans->get("user_id"));
		$owner->init();
	  } else {
		$msg="Numéro de transaction invalide.";
		throw new Exception('KO'); 
	  }
	} else {
	  $msg="Pas de numéro de transaction.";
	  throw new Exception('KO'); 
	}
	
	if ( ! ($me->get("authority_id") == $owner->get("authority_id")) && 
	        $me->canAccess($module->get("name"))){
		$msg="Accès refusé";
		throw new Exception('KO'); 
	  }  
	//}
	
	// Récupération statut
	$status = $zeTrans->getCurrentStatus();
	if ($status !== false) {
	  $msg="Recuperer le status reussi.";
	  $resultatElement->appendChild( $doc->createTextNode( "OK" ));
	  $statusElement->appendChild( $doc->createTextNode( $status ));
	} else {
	  $msg= "Erreur consultation statut.";
	  throw new Exception('KO'); 
	}
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