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
 * \file actes_transac_get_status.php
 * \brief Page de demande de statut d'une transaction
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 11.08.2006
 * 
 *
 * Cette page renvoie le statut d'une transaction
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesClassification.class.php');

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("actes")) {
  echo "KO\nErreur d'initialisation du module";
  exit();
}

$me = new User();

if (! $me->authenticate()) {
  echo "KO\nÉchec de l'authentification";
  exit();
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive() || !$me->canEdit($module->get("name"))) {
  echo "KO\nAccès refusé";
  exit();
}

$myAuthority = new Authority($me->get("authority_id"));

// Recuperation des variables du GET
$transId = Helpers::getVarFromGet("transaction");
$transUniqueId = Helpers::getVarFromGet("unique_id");

if(isset($transUniqueId) && ! empty($transUniqueId)){
	$transId = ActesTransaction::getTransactionFromUniqueId($transUniqueId);	
}


if (isset($transId) && ! empty($transId)) {
	$zeTrans = new ActesTransaction();
	$zeTrans->setId($transId);
} else {
	echo "KO\nNuméro de transaction invalide.";
	exit();
}

if ($zeTrans->init()) {
	$owner = new User($zeTrans->get("user_id"));
	$owner->init();
} else {
	echo "KO\nNuméro de transaction invalide.";
	exit();
  }

$zeEnv = new ActesEnvelope($zeTrans->get("envelope_id"));
if (! $zeEnv->init()) {
  echo "KO\nEnveloppe invalide.";
  exit();
}

// Vérification des permissions
if (! $me->isSuper()) {
  if (! ($me->isAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && ! ($me->getId() == $zeEnv->get("user_id") && $me->canAccess($module->get("name")))) {
	echo "KO\nAccès refusé";
	exit();
  }
}

// Récupération statut
$status = $zeTrans->getCurrentStatus();
if ($status !== false) {
  echo "OK\n" . $status . "\n";
  echo $zeTrans->getFluxRetour($status);
  exit();
} else {
  echo "KO\nErreur consultation statut.";
  exit();
}

?>