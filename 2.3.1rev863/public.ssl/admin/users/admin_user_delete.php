<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à   la
 * dématérialisation de l'administration. 
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
 * associés au chargement,  à   l'utilisation,  à   la modification et/ou au
 * développement et à   la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à   
 * manipuler et qui le réserve donc à   des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à   charger  et  tester  l'adéquation  du
 * logiciel à   leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * à  l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder à cet en-tte signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
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