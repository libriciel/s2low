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
 * \file helios_admin_window_delete.php
 * \brief Page de suppression d'une fenêtre
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 23.08.2006
 * 
 *
 * Cette page effectue la suppression d'une fenêtre
 * de transmission de la base de données
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransmissionWindow.class.php');

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("helios")) {
  $_SESSION["error"] = "Erreur d'initialisation du module";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isSuper() || ! $module->isActive()|| ! $me->canAccess($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

// Récupération des variables du POST
$id = Helpers::getVarFromPost("id");

if (isset($id)) {
  $zeWin = new HeliosTransmissionWindow($id);
  if ($zeWin->delete()) {
	$msg = "Suppression de la fenêtre de transmission " . $zeWin->getId() . ". Résultat ok.";
	if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), $module->get("name"), $me)) {
	  $msg .= "\nErreur de journalisation.";
	}
	
	$_SESSION["error"] = nl2br($msg);
    header("Location: " . WEBSITE_SSL . "/modules/helios/admin/helios_admin_windows.php");
    exit();
  } else {
	$msg = "Erreur lors de la tentative de suppression de la fenêtre de transmission<br />" . $zeWin->getErrorMsg();
	if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, $me->get("role"), $module->get("name"), $me)) {
	  $msg .= "\nErreur de journalisation.";
	}
	
	$_SESSION["error"] = nl2br($msg);
    header("Location: " . WEBSITE_SSL . "/modules/helios/admin/helios_admin_windows.php");
    exit();
  }
} else {
  $_SESSION["error"] = "Pas d'identifiant de fenêtre de transmission spécifié";
  header("Location: " . WEBSITE_SSL . "/modules/helios/admin/helios_admin_windows.php");
  exit();
}
?>