<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, AoÃ»t 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant Ã  la
 * dÃ©matÃ©rialisation de l'administration. 
 *
 * Ce logiciel est rÃ©gi par la licence CeCILL soumise au droit franÃ§ais et
 * respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusÃ©e par le CEA, le CNRS et l'INRIA 
 * sur le site "http://www.cecill.info".
 *
 * En contrepartie de l'accessibilitÃ© au code source et des droits de copie,
 * de modification et de redistribution accordÃ©s par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitÃ©e.  Pour les mÃªmes raisons,
 * seule une responsabilitÃ© restreinte pÃ¨se sur l'auteur du programme,  le
 * titulaire des droits patrimoniaux et les concÃ©dants successifs.
 *
 * A cet Ã©gard  l'attention de l'utilisateur est attirÃ©e sur les risques
 * associÃ©s au chargement,  Ã  l'utilisation,  Ã  la modification et/ou au
 * dÃ©veloppement et Ã  la reproduction du logiciel par l'utilisateur Ã©tant 
 * donnÃ© sa spÃ©cificitÃ© de logiciel libre, qui peut le rendre complexe Ã  
 * manipuler et qui le rÃ©serve donc Ã  des dÃ©veloppeurs et des professionnels
 * avertis possÃ©dant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invitÃ©s Ã  charger  et  tester  l'adÃ©quation  du
 * logiciel Ã  leurs besoins dans des conditions permettant d'assurer la
 * sÃ©curitÃ© de leurs systÃ¨mes et ou de leurs donnÃ©es et, plus gÃ©nÃ©ralement, 
 * Ã l'utiliser et l'exploiter dans les mÃªmes conditions de sÃ©curitÃ©. 
 *
 * Le fait que vous puissiez accÃ©der Ã  cet en-tÃªte signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez acceptÃ© les
 * termes.
*/
?>
<?php
/**
 * \file admin_module_edit_handler.php
 * \brief Page de modification de modules
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 21.07.2006
 * 
 *
 * Cette page permet d'activer ou désactiver un module et ajouter
 * ou modifier ses paramètres.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *  BF	    26.07.2006  Modifications du handler 
 */

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "ehec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isSuper()) {
  $_SESSION["error"] = "Acces refuse";
  header("Location: " . WEBSITE_SSL);
  exit();
}

//! Recuperation des variables du POST
//! Recuperation des informations sur le module
$id = Helpers::getVarFromPost("id");
$status = Helpers::getVarFromPost("status");

//! On récupère des informations sur les parametres du module sous forme de tableaux
$param_id = Helpers::getVarFromPost("param_id");
$param_name = Helpers::getVarFromPost("param_name");
$param_description = Helpers::getVarFromPost("param_description");
$param_value = Helpers::getVarFromPost("param_value");
$param_to_suppr = Helpers::getVarFromPost("param_to_suppr");

//! On recupere les informations concernant le paramètre du module a ajouter
$new_param_name = Helpers::getVarFromPost("new_param_name");
$new_param_value = Helpers::getVarFromPost("new_param_value");
$new_param_description = Helpers::getVarFromPost("new_param_description");

$modules = new Module();

if (isset($id) && ! empty($id)) {
  $modules->setId($id);
  if (! $modules->init()) {
    $_SESSION["error"] = "Erreur lors de la modification de la collectivité";
    header("Location: " . WEBSITE_SSL . "/admin/authorities/admin_modules.php");
    exit();
  }
}

$modules->set("status", $status);

if (is_array($param_id) && count($param_id) > 0) {
  foreach ($param_id as $key => $value_id) {	
	if (is_array($param_to_suppr)) {
	  if (! in_array($value_id,$param_to_suppr)) {
		$modules->setModuleParams($param_name[$key],$param_description[$key],$param_value[$key]);
	  }
	} else {
	  $modules->setModuleParams($param_name[$key],$param_description[$key],$param_value[$key]);
	}
  }
}

if (strlen($new_param_name) > 0 && strlen($new_param_description) > 0 && strlen($new_param_value) > 0) {
  $modules->setModuleParams($new_param_name,$new_param_description,$new_param_value);
}

if (! $modules->save()) {
  $msg = "Erreur lors de l'enregistrement du module :\n" . $modules->getErrorMsg();
  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, $me->get("role"), false, $me)) {
	$msg .= "\nErreur de journalisation.";
  }

  $_SESSION["error"] = nl2br($msg);
  header("Location: " . WEBSITE_SSL . "/admin/modules/admin_modules.php");
  exit();
} else {
  $msg = ($modules->isNew()) ? "Création" : "Modification";
  $msg .= " du module " . $modules->get("name") . " (id=" . $modules->getId() . "). Résultat ok.";
  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
	$msg .= "\nErreur de journalisation.";
  }

  $_SESSION["error"] = nl2br($msg);
  header("Location: " . WEBSITE_SSL . "/admin/modules/admin_module_edit.php?id=" . $modules->getId());
}

exit();
?>