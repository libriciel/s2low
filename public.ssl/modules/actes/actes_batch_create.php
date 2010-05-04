<?php
/*
 * T�D�TIS - Copyright 2006 Alternance-Soft
 * Contributeur : J�r�me Schell, Août 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant �  la
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
 * associés au chargement,  �  l'utilisation,  �  la modification et/ou au
 * développement et �  la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe �  
 * manipuler et qui le réserve donc �  des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités �  charger  et  tester  l'adéquation  du
 * logiciel �  leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * � l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder �  cet en-tête signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/
/**
 * \file public.ssl/modules/actes/actes_batch_create.php
 * \brief Page de cr�ation d'une transaction par lots
 * \author J�r�me Schell <j.schell@alternancesoft.com>
 * \date 08.02.2007
 * 
 *
 * Cette page cr�e un lot dans la BDD
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesBatch.class.php');

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
  Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (!$me->authenticate()) {
  Helpers::returnAndExit(1, "�chec de l'authentification", WEBSITE);
}

if (!$module->isActive() || ! $me->canAccess($module->get("name"))) {
  Helpers::returnAndExit(1, "Acc�s refus�", WEBSITE_SSL);
}

$description = Helpers::getVarFromPost("intitule");
$num_prefix = Helpers::getVarFromPost("prefixe");

$zeBatch = new ActesBatch();

$zeBatch->set("description", $description);
$zeBatch->set("num_prefix", $num_prefix);
$zeBatch->set("user_id", $me->getId());

if (count($_FILES) > 0) {
  if (! $zeBatch->importFilesFromForm($_FILES)) {
	Helpers::returnAndExit(1, $zeBatch->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_batch_add.php");
  } else {
	if (! $zeBatch->save()) {
	  $msg = "Erreur lors de l'enregistrement du lot : " . $zeBatch->getErrorMsg();
	  
	  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
		$msg .= "\nErreur de journalisation.";
	  }

	  Helpers::returnAndExit(1, $msg, WEBSITE_SSL . "/modules/actes/actes_batch_add.php");
	} else {
	  $msg = "Lot n�" . $zeBatch->getId() . " cr�� avec succ�s.";
	  
	  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
		$msg .= "\nErreur de journalisation.";
	  }

	  Helpers::returnAndExit(0, $msg, WEBSITE_SSL . "/modules/actes/actes_batch_show.php?id=" . $zeBatch->getId(), $zeBatch->getId());
	}
  }
} else {
	Helpers::returnAndExit(1, "Aucun fichier soumis.", WEBSITE_SSL . "/modules/actes/actes_batch_add.php");
}


?>