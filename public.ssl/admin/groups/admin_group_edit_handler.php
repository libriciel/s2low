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
 * \file admin_group_edit_handler.php
 * \brief Page de traitement des modifications ou ajout de groupe
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 16.01.2007
 * 
 *
 * Cette page effectue le traitement d'ajout ou de modification d'un
 * groupe dans la base de données
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

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

// Recuperation des variables du POST
$id = Helpers::getVarFromPost("id");
$mode = Helpers::getVarFromPost("mode");
$name = Helpers::getVarFromPost("name", true);
$status = Helpers::getVarFromPost("status", true);
$siren_file = $_FILES["siren_file"];

$group = new Group();
$mod = false;

if (isset($id) && ! empty($id)) {
  $group->setId($id);
  $mod = true;
  if (! $group->init()) {
    $_SESSION["error"] = "Erreur lors de la modification du groupe";
    header("Location: " . WEBSITE_SSL . "/admin/groups/admin_groups.php");
    exit();
  }
}

$group->set("name", $name);
$group->set("status", $status);

# Traitement de la liste des SIREN autorisés
if (isset($siren_file["tmp_name"]) && strlen($siren_file["tmp_name"]) > 0) {
  if (! is_uploaded_file($siren_file["tmp_name"])) {
    $_SESSION["error"] = "Envoi de fichier incorrect.";
    header("Location: " . WEBSITE_SSL . "/admin/groups/admin_groups.php");
    exit();
  }

  if (! $group->importSiren($siren_file["tmp_name"])) {
    $_SESSION["error"] = "Échec lors de l'import du fichier SIREN : " . $group->getErrorMsg();
    header("Location: " . WEBSITE_SSL . "/admin/groups/admin_groups.php");
    exit();
  }
}

if (! $group->save()) {
  $msg = "Erreur lors de l'enregistrement du groupe :\n" . $group->getErrorMsg();
  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3,false, $me->get("role"), false, $me)) {
	$msg .= "\nErreur de journalisation.";
  }

  $_SESSION["error"] = nl2br($msg);

  if ($group->isNew()) {
	header("Location: " . WEBSITE_SSL . "/admin/groups/admin_group_edit.php");
  } else {
	header("Location: " . WEBSITE_SSL . "/admin/groups/admin_group_edit.php?id=" . $group->getId());
  }

  exit();
} else {
  Helpers::purgeTempSession();
  $msg = ($mod) ? "Modification" : "Création";
  $msg .= " du groupe " . $group->get("name") . " (id=" . $group->getId() . "). Résultat ok.";
  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
	$msg .= "\nErreur de journalisation.";
  }

  $_SESSION["error"] = nl2br($msg);
  header("Location: " . WEBSITE_SSL . "/admin/groups/admin_group_edit.php?id=" . $group->getId());
}
?>