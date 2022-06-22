<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à la
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
 * \file actes_batch_delete.php
 * \brief Page de suppression d'un lot de fichier transaction Actes
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 14.02.2007
 * 
 *
 * Ce scipt permet de supprimer un lot ainsi que tous les fichiers associés
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesBatch.class.php');

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("actes")) {
  Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (! $me->authenticate()) {
  Helpers::returnAndExit(1, "Échec de l'authentification", WEBSITE);
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive() || ! $me->canAccess($module->get("name"))) {
  Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

try{
    $id = Helpers::getIntFromPost("id");
} catch (Exception $e){
    Helpers::returnAndExit(1, $e->getMessage(), WEBSITE_SSL . "/modules/actes/actes_batch_handle.php");
}

$myAuthority = new Authority($me->get("authority_id"));

$zeBatch = new ActesBatch();

if (isset($id) && ! empty($id)) {
  $zeBatch->setId($id);
  if ($zeBatch->init()) {
	$owner = new User($zeBatch->get("user_id"));
	$owner->init();
  } else {
	Helpers::returnAndExit(1, "Erreur d'initialisation du lot.", WEBSITE_SSL . "/modules/actes/actes_batch_handle.php");
  }
} else {
  Helpers::returnAndExit(1, "Pas d'identifiant de lot spécifié", WEBSITE_SSL . "/modules/actes/actes_batch_handle.php");
}

// Vérification des permissions
if (! $me->isSuper()) {
  if (! ($me->isAuthorityAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && ($me->getId() != $owner->getId())) {
	Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL . "/modules/actes/actes_batch_handle.php");
  }
}

if (! $zeBatch->delete()) {
  $msg = "Erreur lors de la suppression du lot :\n" . $zeBatch->getErrorMsg();

  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
	$msg .= "\nErreur de journalisation.";
  }

  Helpers::returnAndExit(1, $msg, WEBSITE_SSL . "/modules/actes/actes_batch_handle.php");
} else {
  $msg = "Suppression du lot n°" . $zeBatch->getId() . ". Résultat ok.";

  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
	$msg .= "\nErreur de journalisation.";
  }

  Helpers::returnAndExit(0, $msg, WEBSITE_SSL . "/modules/actes/actes_batch_handle.php");
}

?>
