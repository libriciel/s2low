<?php

/**
 * Ce script lance la notification manuelle de l'acquittement d'un acte
 *
 * NOTE : Ce script a l'air de faire partie de l'API (il prend en charge une liste d'id à notifier....) (EP) */

require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
  Helpers :: returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

$sortie = "";

if (!$me->authenticate()) {
  Helpers :: returnAndExit(1, "Échec de l'authentification", WEBSITE);
}

// Un super admin ne peut pas accéder à cette page
if (!$module->isActive() || $me->isGroupAdminOrSuper() || !$me->canEdit($module->get("name"))) {
  Helpers :: returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$liste_id = array ();

if (Helpers :: getVarFromPost("id")) {
  $liste_id[] = Helpers :: getVarFromPost("id");
} else {
  $liste_id = Helpers :: getVarFromPost("liste_id");
}

if (! $liste_id){
	Helpers :: returnAndExit(1, "Pas d'identifiant de transaction spécifié.", WEBSITE_SSL . "/modules/actes/index.php");
}


foreach ($liste_id as $id) {
	$trans = new ActesTransaction();
	$trans->setId($id);
    
    if (! $trans->init()) {
      Helpers :: returnAndExit(1, "Erreur d'initialisation de la transaction.", WEBSITE_SSL . "/modules/actes/index.php");
    }
    	
    $owner = new User($trans->get("user_id"));
    $owner->init();
    
	//Vérification du type de transaction
	if ($trans->get("type") != 1) {
		Helpers :: returnAndExit(1, "Ce type de transaction ne peut pas être notifié.", WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $rel_trans->getId());
	}

	// Vérification des permissions
	if (!($me->isAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && !($me->canEdit($module->get("name")))) {
		Helpers :: returnAndExit(1, "Accès refusé.", WEBSITE_SSL . "/modules/actes/index.php");
	}
  
	$broadcastEmail = Helpers :: getVarFromPost("broadcast_email");		
	if ($broadcastEmail){
		if ($trans->setNotification(implode(',',Helpers :: getVarFromPost("broadcast_email")), (Helpers :: getVarFromPost("send_sources") == 'on') ? 1 : 0)) {
			$severity = 1;
     		$msg = "Notification manuelle de la transaction " . $id;
     		$sortie .= $msg;
   		} else {
	     $severity = 3;
	     $msg = "Erreur lors de la notification de la transaction " . $id;
	     $sortie .= $msg;
	   }
	}

    $actesNotification = $objectInstancier->get('ActesNotification');
    $actesNotification->sendAutomaticNotification();

    if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, $severity, false, 'USER', $module->get("name"), $me)) {
		$msg .= "\nErreur de journalisation.\n";
    	$sortie .= $msg;
	}
  
  
  
}

  
if (count($liste_id) == 1) {
  $retour = WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $liste_id[0];
} else {
  $retour = WEBSITE_SSL . "/modules/actes/index.php";
}
$status = 0;
Helpers :: returnAndExit($status, $sortie, $retour);
