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
 * \file admin_send_global_message.php
 * \brief Page d'envoi massif de mails
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 22.08.2006
 * 
 *
 * Cette page envoie un message au utilisateur d'un module.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */


// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/class/Mailer.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isSuper()) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$moduleId = Helpers::getVarFromPost("module");
$subject = Helpers::getVarFromPost("subject");
$body = Helpers::getVarFromPost("body");

if (empty($subject) || empty($body)) {
  $_SESSION["error"] = "Données manquantes pour l'envoi du message.";
  header("Location: " . WEBSITE_SSL . "/admin/utilities/");
  exit();
}

if (! empty($moduleId)) {
  $module = new Module($moduleId);
  if (! $module->init()) {
	$_SESSION["error"] = "Module incorrect spécifié.";
	header("Location: " . WEBSITE_SSL . "/admin/utilities/");
	exit();
  }
} else {
  $_SESSION["error"] = "Pas de module spécifié.";
  header("Location: " . WEBSITE_SSL . "/admin/utilities/");
  exit();
}

// Récupération des destinataires du message
// (tous les utilisateurs du module concerné)
if (! $recipients = $module->getUsers()) {
  $_SESSION["error"] = "Récupération destinataire impossible.<br />" . $module->getErrorMsg();
  header("Location: " . WEBSITE_SSL . "/admin/utilities/");
  exit();
}

$mailer = new Mailer();
foreach($recipients as $recipient){
	$mailer->addComplexRecipient($recipient); 
}

if (! $mailer->sendMail($subject, $body)) {
  $msg = "Erreur lors de l'envoi de message aux " . count($recipients) . " utilisateurs du module " . $module->get("name") . ".\n";
  $msg .= $mailer->getLastError();
  $status = 3;
} else {
  $msg = "Envoi de message aux " . count($recipients) . " utilisateurs du module " . $module->get("name") . ". Résultat ok.";
  $status = 1;
}

if (! Log::newEntry(LOG_ISSUER_NAME, $msg, $status, false, 'SADM', $module->get("name"), $me)) {
  $msg .= "\nErreur de journalisation.";
}

$_SESSION["error"] = nl2br($msg);

header("Location: " . WEBSITE_SSL . "/admin/utilities/");
?>