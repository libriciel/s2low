<?php

/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Cristina Pop   Mars 2007 
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
 * \file actes_download_file.php
 * \brief Page de téléchargement des fichiers d'une transaction
 * \author Cristina Pop <cpop@alternancesoft.com> , Jérôme Schell <j.schell@alternancesoft.com>
 * \date 29.01.2007
 * 
 *
 * Cette page permet de télécharger un fichier  à partir de l'id de sa transaction
 * 
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("helios")) {
  $_SESSION["error"] = "Erreur d'initialisation du module";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$me = new User();

if (!$me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit ();
}

if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$transaction_id = Helpers :: getVarFromGet("id");

//tmp
//echo "Transaction:" . $transaction_id;

$myAuthority = new Authority($me->get("authority_id"));

$entity = new HeliosTransaction($transaction_id);
$filename=$entity->getFilenameForId($transaction_id);
$sha1=$entity->getSha1ForId($transaction_id);

$ownerId = $entity->getUserForId($transaction_id);
$owner = new User($ownerId);
$owner->init();

// Vérification des permissions
/*
if (! $me->isSuper()) {
  if (! ($me->isAuthorityAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && ! ($me->getId() == $owner->getId() && $me->canAccess($module->get("name")))) {
	$_SESSION["error"] = "Accès refusé";
	header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
	exit();
  }
}

*/

if (!$entity->sendfile($sha1,$filename)) {
  $_SESSION["error"] = "Erreur d'envoi du fichier " . $filename . " : " . $entity->getErrorMsg();
  //header("Location: " . WEBSITE_SSL);
  exit ();
}
?>
