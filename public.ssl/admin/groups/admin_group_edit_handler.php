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
  $_SESSION["error"] = "Acces refuse";
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