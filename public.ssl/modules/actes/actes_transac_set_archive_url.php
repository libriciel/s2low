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
 * \file actes_transac_set_archive_url.php
 * \brief Page de définition de l'url d'archive pour une transaction
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 01.02.2007
 * 
 *
 * Cette page permet de définir l'url d'archive pour une transaction donnée
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("actes")) {
  Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (! $me->authenticate()) {
  Helpers::returnAndExit(1, "Échec de l'authentification", WEBSITE);
}

if ($me->isSuper() || ! $module->isActive() || ! $me->canAccess($module->get("name"))) {
  Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$id = Helpers::getVarFromPost("id");
$url = Helpers::getVarFromPost("url");

if (isset($id) && is_numeric($id)) {
  $trans = new ActesTransaction($id);

  if (! $trans->init()) {
	Helpers::returnAndExit(1, "Erreur d'initialisation de la transaction.", WEBSITE_SSL . "/modules/actes/index.php");
  }

  // Vérification des permissions
  if (! $trans->userCanEdit($me)) {
	Helpers :: returnAndExit(1, "Accès refusé.", WEBSITE_SSL . "/modules/actes/index.php");
  }

  if (! isset($url) || empty($url)) {
	Helpers::returnAndExit(1, "Pas d'url spécifiée pour l'archivage.", WEBSITE_SSL . "/modules/actes/index.php");
  }

  $trans->set("archive_url", $url);

  if (! $trans->save()) {
	$msg = "Erreur lors de l'enregistrement de la transaction :\n" . $trans->getErrorMsg();
	if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
	  $msg .= "\nErreur de journalisation.";
	}

	Helpers::returnAndExit(1, $msg, WEBSITE_SSL . "/modules/actes/index.php");
  } else {
	$msg = "Définition de l'URL d'archivage pour la transaction n°" . $trans->getId() . ". Résultat ok.";
	if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
	  $msg .= "\nErreur de journalisation.";
	}
	
	Helpers::returnAndExit(0, $msg, WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $trans->getId());
  }
} else {
  Helpers::returnAndExit(1, "Pas d'identifiant de transaction spécifié ou type invalide.", WEBSITE_SSL . "/modules/actes/index.php");
}

