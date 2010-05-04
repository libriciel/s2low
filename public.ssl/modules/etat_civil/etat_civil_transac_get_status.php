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
require_once(SITEROOT . '/public.ssl/modules/etat_civil/class/etat_civilTransaction.class.php');
//require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesClassification.class.php');

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("etat_civil")) {
  echo "KO \n Erreur d'initialisation du module";
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


$myAuthority = new Authority($me->get("authoritgetCurrentStatus()y_id"));

// Recuperation des variables du GET
$transId = Helpers::getVarFromGet("transaction");

$zeTrans = new etat_civilTransaction();

if (isset($transId) && ! empty($transId)) {
  $zeTrans->setId($transId);
  if ($zeTrans->init()) {
	$owner = new User($zeTrans->get("user_id"));
	$owner->init();
  } else {
	echo "KO\nNuméro de transaction invalide.";
	exit();
  }
} else {
  echo "KO\Pas de numéro de transaction.";
  exit();
}

// Vérification des permissions
/*
if (! $me->isSuper()) {
  if (! ($me->isAdmin() && 
*/
if ( ! ($me->get("authority_id") == $owner->get("authority_id")) && 
        $me->canAccess($module->get("name"))){
	echo "KO\nAccès refusé";
	exit();
  }  
//}

// Récupération statut
$status = $zeTrans->getCurrentStatus();
if ($status !== false) {
  echo "OK\n" . $status . "\n";
  echo " <br> A modifier etat_civil_transac_get_status.php (vérification de permission)";
  exit();
} else {
  echo "KO\nErreur consultation statut.";
  exit();
}


?>