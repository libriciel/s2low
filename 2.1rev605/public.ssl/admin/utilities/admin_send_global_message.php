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

foreach($recipients as $recipient){
	$mailer = new Mailer();
	$mailer->addComplexRecipient($recipient); 

	if (! $mailer->sendMail($subject, $body)) {
	  $msg = "Erreur lors de l'envoi de message a " . $recipient['email'] . " utilisateurs du module " . $module->get("name") . ".\n";
	  $msg .= $mailer->getLastError();
	  $status = 3;
	}
}

$msg = "Envoi de message aux " . count($recipients) . " utilisateurs du module " . $module->get("name") . ". Résultat ok.";
$status = 1;


if (! Log::newEntry(LOG_ISSUER_NAME, $msg, $status, false, 'SADM', $module->get("name"), $me)) {
  $msg .= "\nErreur de journalisation.";
}

$_SESSION["error"] = nl2br($msg);

header("Location: " . WEBSITE_SSL . "/admin/utilities/");
?>