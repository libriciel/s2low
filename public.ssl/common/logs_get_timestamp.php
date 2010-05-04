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
 * \file logs_get_timestamp.php
 * \brief Page de téléchargement de l'entrée de logs accompagnée de son horodatage
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 21.08.2006
 * 
 *
 * Ce script permet de télécharger une archive zip contenant l'entrée de journal
 * accompagnée de son fichier d'horodatage.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

$id = Helpers::getVarFromGet("id");

$myAuthority = new Authority($me->get("authority_id"));

$log = new Log();

if (isset($id) && ! empty($id)) {
  $log->setId($id);
  if (! $log->init()) {
    $_SESSION["error"] = "Erreur lors de l'initialisation de l'entrée de journal.";
    header("Location: " . WEBSITE_SSL . "/common/logs_view.php");
    exit();
  }
} else {
  $_SESSION["error"] = "Pas d'identifiant de log spécifié.";
  header("Location: " . WEBSITE_SSL . "/common/logs_view.php");
  exit();
}

// Vérification des permissions sur l'entrée de journal
if (! $log->canView($me)) {
  $_SESSION["error"] = "Accès refusé.";
  header("Location: " . WEBSITE_SSL . "/common/logs_view.php");
  exit();
}

if (! $log->sendArchive()) {
  $_SESSION["error"] = "Erreur de récupération de l'entrée de log et de son horodatage.<br />" . $log->getErrorMsg();
  header("Location: " . WEBSITE_SSL . "/common/logs_view.php");
}

exit();
?>
