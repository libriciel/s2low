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
 * \file actes_transac_cancel.php
 * \brief Page de demande d'annulation d'une transaction acte
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 09.08.2006
 * 
 *
 * Ce script crée une transaction d'annulation pour une autre transaction
 * acte.
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

// Un super admin ne peut pas accéder à cette page
if (! $module->isActive() || $me->isGroupAdminOrSuper() || ! $me->canEdit($module->get("name"))) {
  Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

if ($module->getParam("paper") == "on") {
  Helpers::returnAndExit(1, "Mode « papier » actif. Accès interdit.", WEBSITE_SSL . "/modules/actes/");
}

$related_id = Helpers::getVarFromPost("id");

$myAuthority = new Authority($me->get("authority_id"));

$rel_trans = new ActesTransaction();

if (isset($related_id) && ! empty($related_id)) {
  $rel_trans->setId($related_id);
  if ($rel_trans->init()) {
	$owner = new User($rel_trans->get("user_id"));
	$owner->init();
  } else {
	Helpers::returnAndExit(1, "Erreur d'initialisation de la transaction.", WEBSITE_SSL . "/modules/actes/index.php");
  }
} else {
  Helpers::returnAndExit(1, "Pas d'identifiant de transaction à annuler spécifié.", WEBSITE_SSL . "/modules/actes/index.php");
}

// Vérification du type de transaction
if ($rel_trans->get("type") != 1) {
  Helpers::returnAndExit(1, "Ce type de transaction ne peut pas être annulé.", WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $rel_trans->getId());
}

if ($rel_trans->hasPendingCancelTrans()) {
  Helpers::returnAndExit(1, "Une demande d'annulation est déjà en cours pour cette transaction.", WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $rel_trans->getId());
}

$rel_envelope = new ActesEnvelope($rel_trans->get("envelope_id"));
$rel_envelope->init();

// Vérification des permissions
if (! ($me->isAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && ! ($me->getId() == $rel_envelope->get("user_id") && $me->canEdit($module->get("name")))) {
  Helpers::returnAndExit(1, "Accès refusé.", WEBSITE_SSL . "/modules/actes/index.php");
}

if ($rel_trans->getCurrentStatus() != 4) {
  Helpers::returnAndExit(1, "Impossible d'annuler une transaction qui n'est pas en état « Acquittement reçu ».", WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $rel_trans->getId());
}

$env = new ActesEnvelope();
$trans = new ActesTransaction();
$transNatures = ActesTransaction::getTransactionNaturesIdDescr();

// Définition des adresses de retour
$retMail = array();
$retMail[] = ACTES_TDT_MAIL_ADDRESS;

if ($me->get("email")) {
  $retMail[] = $me->get("email");
}
if ($myAuthority->get("email")) {
  $retMail[] = $myAuthority->get("email");
}

// Téléphone du contact
if ($me->get("telephone")) {
  $telephone = $me->get("telephone");
} else {
  $telephone = $myAuthority->get("telephone");
}

// Initialisation de l'enveloppe
$env->set("user_id", $me->getId());
$env->set("siren", $myAuthority->get("siren"));
$env->set("department", $myAuthority->get("department"));
$env->set("district", $myAuthority->get("district"));
$env->set("authority_type_code", $myAuthority->get("authority_type_id"));
$env->set("return_mail", implode($retMail, '|'));
$env->set("name", $me->getprettyName());
$env->set("telephone", $telephone);
$env->set("email", $me->get("email"));

// Initialisation de la transaction
$trans->set("type", "6");
$trans->set("related_transaction", $rel_trans);
$trans->set("related_transaction_id", $related_id);
$trans->set("number", $rel_trans->get("number"));
$trans->set("unique_id", $rel_trans->get("unique_id"));

// Destination de création des fichiers
$dest = $env->get("siren") . "/" . $trans->get("number") . "/";
$trans->set("destDir", $dest);
$env->set("destDir", $dest);

// Génération du fichier XML de la transaction
$xml_name = $trans->getStdFileName($env, false);
if (! $trans->generateMessageXMLFile($xml_name)) {
  Helpers::returnAndExit(1, "Erreur lors de la génération du message métier.", WEBSITE_SSL . "/modules/actes/actes_transac_add.php");
}

$env->addTransaction($trans);



require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelopeSerialSQL.class.php');

$authority_id = $me->get("authority_id");

$actesEnvelopeSerial = new ActesEnvelopeSerialSQL(DatabasePool::getInstance());
$serialNumber = $actesEnvelopeSerial->getNext($authority_id);

// Génération du fichier XML de l'enveloppe
if (! $env->generateEnvelopeXMLFile($serialNumber)) {
  Helpers::returnAndExit(1, "Erreur lors de la génération de l'enveloppe.", WEBSITE_SSL . "/modules/actes/actes_transac_add.php");
}

// Création de l'archive .tar.gz
if (! $env->generateArchiveFile()) {
  Helpers::returnAndExit(1, "Erreur lors de la génération de l'archive.\n" . $env->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_transac_add.php");
}

// Purge des fichiers intermédiaires
$env->purgeFiles();

//print_r($env);
//print_r($trans);

//exit();

if (! $env->save()) {
  $msg = "Erreur lors de l'enregistrement de l'enveloppe&nbsp;:\n" . $env->getErrorMsg();

  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
	$msg .= "\nErreur de journalisation.";
  }

  Helpers::returnAndExit(1, $msg, WEBSITE_SSL . "/modules/actes/index.php");
}

$trans->set("envelope_id", $env->getId());

if (! $trans->save()) {
  $msg = "Erreur lors de l'enregistrement de la transaction.<br />" . $trans->getErrorMsg();

  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
	$msg .= "\nErreur de journalisation.";
  }

  $env->deleteArchiveFile();
  $env->delete();

  Helpers::returnAndExit(1, $msg, WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $rel_trans->getId());
} else {
  $msg = "Création transaction d'annulation réussie. Enveloppe n°" . $env->getId() . " créée.";

  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
	$msg .= "\nErreur de journalisation.";
  }

  Helpers::purgeTempSession();

  // Message réservé à l'appel via API
  // Id de transaction créée
  $apiMsg = $trans->getId() . "\n";

  Helpers::returnAndExit(0, $msg, WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $rel_trans->getId(), $apiMsg);
}
?>