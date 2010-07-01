<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à la
 * dématèrialisation de l'administration. 
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
 * \file actes_download_file.php
 * \brief Page de téléchargement des fichiers d'une transaction
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 29.01.2007
 * 
 *
 * Cette page permet de télécharger les fichiers d'une transaction
 * (enveloppe ou fichier individuels)
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
//require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');
//require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesIncludedFile.class.php');
require_once(SITEROOT . '/public.ssl/modules/etat_civil/class/etat_civilTransaction.class.php');

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("etat_civil")) {
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

if (! $module->isActive()|| ! $me->canAccess($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$transaction_Id = Helpers::getVarFromGet("id");
//tmp
echo "<br>ec: download_file: TRANSCATION id:".$transaction_id;


/*
$envId
$fileId = Helpers::getVarFromGet("file");
$type = Helpers::getVarFromGet("type");
*/

$myAuthority = new Authority($me->get("authority_id"));

$entity = new etat_civilTransaction();
$filename = $entity -> getFilenameForId($transaction_id);

echo "<br>ec: download_file: filename".$filename;


$ownerId =  $entity ->getUserForId($transaction_id);

//tmp
echo "<br>ec: download_file: owner_id".$ownerId;

$owner = new User($ownerId);
$owner->init();

// Vérification des permissions
/* A FAIRE...
if (! $me->isSuper()) {
  if (! ($me->isAuthorityAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && ! ($me->getId() == $owner->getId() && $me->canAccess($module->get("name")))) {
	$_SESSION["error"] = "Accès refusé";
	header("Location: " . WEBSITE_SSL . "/modules/etat_civil/index.php");
	exit();
  }
}

*/


if (! $entity->sendfile($filename)) {
  $_SESSION["error"] = "Erreur d'envoi du fichier : " . $entity->getErrorMsg();
  header("Location: " . WEBSITE_SSL);
  exit();
}

?>
