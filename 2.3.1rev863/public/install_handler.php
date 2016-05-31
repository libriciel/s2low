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
