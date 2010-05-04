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
 * \file install_handler.php
 * \brief Page de traitement de l''initialisation du site Tedetis
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 19.08.2006
 * 
 *
 * Page de traitement de l'initialisation du site TéDéTis (non authentifié).
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */


// Configuration
require_once("../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

// Vérification si l'application est déjà configurée ou pas
if (User::dbHasUser()) {
  $_SESSION["error"] = "L'application est déjà configurée.";
  header("Location: " . WEBSITE);
  exit();
}

// Récupération des variables du POST
$name = Helpers::getVarFromPost("name", true);
$givenname = Helpers::getVarFromPost("givenname", true);
$email = Helpers::getVarFromPost("email", true);
$telephone = Helpers::getVarFromPost("telephone", true);
$certificate = $_FILES["certificate"];

$him = new User();

$him->set("name", $name);
$him->set("givenname", $givenname);
$him->set("email", $email);
$him->set("telephone", $telephone);
$him->set("status", 1);
$him->set("authority_id", 1);
$him->set("role", 'SADM');

if (is_array($certificate) && count($certificate) > 0 && is_uploaded_file($certificate["tmp_name"])) {
  $him->set("certFilePath", $certificate["tmp_name"]);
} else {
  $_SESSION["error"] = "Le certificat utilisateur est obligatoire<br />";
  header("Location: " . WEBSITE . "/install.php");
  exit();
}

if (! $him->save()) {
  $_SESSION["error"] = "Erreur lors de l'enregistrement de l'utilisateur :<br />" . $him->getErrorMsg();
  header("Location: " . WEBSITE . "/install.php");
  exit();
} else {
  Helpers::purgeTempSession();
  $_SESSION["error"] = "Utilisateur créé avec succès";
  header("Location: " . WEBSITE);
  exit();
}
?>
