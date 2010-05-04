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
 * \file admin_user_delete.php
 * \brief Page effectuant la suppression d'un utilisateur
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 16.03.2006
 * 
 *
 * Cette page supprime un utilisateur de la base de données
 * Elle prend un paramètre id dans la requête HTTP POST designant
 * l'utilisateur à supprimer.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *  JS   19.07.2006  Adaptation pour Tedetis
 */

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

// Suppression utilisateur désactivée
header("Location: " . WEBSITE);

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isAdmin()) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$id = isset($_POST["id"]) ? $_POST["id"] : null;

if (isset($id) && ! empty($id)) {
  $him = new User($id);

  if (! $me->canEditUser($id)) {
	$_SESSION["error"] = "Accès refusé pour la suppression de cet utilisateur";
	header("Location: " . WEBSITE_SSL . "/admin/users/admin_users.php");
	exit();
  } else {
	if ($him->delete()) {
	  $msg = "Suppression de l'utilisateur " . $him->getPrettyName() . ". Résultat ok.";
	  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
		$msg .= "\nErreur de journalisation.";
	  }
	
	  $_SESSION["error"] = nl2br($msg);
	  header("Location: " . WEBSITE_SSL . "/admin/users/admin_users.php");
	  exit();
	} else {
	  $msg = "Erreur lors de la tentative de suppression de l'utilisateur\n" . $him->getErrorMsg();
	  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, $me->get("role"), false, $me)) {
		$msg .= "\nErreur de journalisation.";
	  }
	
	  $_SESSION["error"] = nl2br($msg);
	  header("Location: " . WEBSITE_SSL . "/admin/users/admin_users.php");
	  exit();
	}
  }
} else {
  $_SESSION["error"] = "Pas d'identifiant utilisateur spécifié";
  header("Location: " . WEBSITE_SSL . "/admin/users/admin_users.php");
  exit();
}
?>