<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : C. Pop Mars 2007
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
 * \file actes_transac_show.php
 * \brief Page d'affichage d'une transaction Actes
 * \author Cristina Pop <cpop@alternancesoft.com>, J. Shell <j.schell@alternancesoft.com>
 * \date 21.03.2007
 * 
 *
 * Cette page affiche les détails d'une transaction Actes et 
 * permet de demander son annulation et de la valider
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');
require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransactionWorkflow.class.php');


// Instanciation du module courant
$module = new Module();
if (! $module->initByName("helios")) {
  $_SESSION["error"] = "Erreur d'initialisation du module";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Éhec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $module->isActive()|| ! $me->canAccess($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$id = Helpers::getVarFromGet("id");



$myAuthority = new Authority($me->get("authority_id"));


$trans = new HeliosTransaction();

if (isset($id) && ! empty($id)) {
  $trans->setId($id);
  if ($trans->init()) {//obtine inregistrarea ce corespunde 
	$owner = new User($trans->get("user_id")); //!!!!! din HeliosTransaction
	$owner->init();
  } else {
	$_SESSION["error"] = "Erreur d'initialisation de la transaction.";
	header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
	exit();
  }
} else {
  $_SESSION["error"] = "Pas d'identifiant de transaction spécifié";
  header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
  exit();
}


// Vérification des permissions
if (! $me->isSuper()) {
  if (! ($me->isAuthorityAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && ! ($me->getId() == $trans->get("user_id") && $me->canAccess($module->get("name")))) {
	$_SESSION["error"] = "Accès refusé";
	header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
	exit();
  }
}

$doc = new HTMLLayout();

$doc->setTitle("Helios : visualisation de transactions pour un fichier");

$doc->buildMenu($me);

//$transStatus = $trans->getCurrentStatus(); //CURRENT STATUS

$currentStatus=HeliosTransactionWorkflow::getCurrentStatus($id);
$html = "<div id=\"content\">\n";



$html .= "<center><a href=\"" . WEBSITE_SSL . "/modules/helios/\" class=\"bouton\">Retour liste transactions</a></center>\n";
$html .= "<h2>Visualisation de transactions d'un fichier</h2>\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data\">\n";
$html .= $doc->getHTMLArrayline("Fichier", $trans->getFilenameForID($id)); 
$html .= $doc->getHTMLArrayline("Date de postage" ,Helpers :: getDateFromBDDDate(HeliosTransactionWorkflow::getDatePoste($id), true));
$html .= $doc->getHTMLArrayline("État actuel" ,$currentStatus);
$html .= $doc->getHTMLArrayline("Taille (octets)" ,$trans->get("file_size"));
$html .= $doc->getHTMLArrayline("Empreinte SHA1" ,$trans->get("sha1"));

$url = "Non définie";
$html .= $doc->getHTMLArrayline("URL d'archivage", $url);
$html .= "</table>\n";
$html .= "</div>\n";
$html .= "<br />\n";



$html .= "<h3>Récuperation du fichier posté ";
$html .= "<a href=\"" .WEBSITE_SSL. "/modules/helios/helios_download_file.php?id=" .$id. "\" title=\"Télécharger le fichier\">".$trans->getFilenameForID($id)."</a> </h3>";

// Affichage du Workflow

$workflow = $trans->fetchWorkflow();
$status = HeliosTransaction::getStatusList();

$html .= "<h3>Cycle de vie de la transaction</h3>\n";

if (count($workflow) > 0) {
  $html .= "<div class=\"data_table\">\n";
  $html .= "<table class=\"workflow_list\">\n";
  $html .= " <tr>\n";
  $html .= "  <th>Etat</th>\n";
  $html .= "  <th>Date</th>\n";
  $html .= "  <th>Message</th>\n";
  $html .= " </tr>\n";

  foreach ($workflow as $stage) {
	$html .= " <tr>\n";
	$html .= "  <td>" . $status[$stage["status_id"]] ;
  	if($stage["status_id"] == 4 || $stage["status_id"] == 6 )
       $html .= " <a href=\"" .WEBSITE_SSL. "/modules/helios/helios_download_acquit.php?id=" .$id. "\" title=\"Télécharger l'acquittement\">voir</a> </h3>";
    $html .= "</td>\n";
	$html .= "  <td>" . Helpers::getDateFromBDDDate($stage["date"], true) . "</td>\n";
	$html .= "  <td class=\"long_field\">" . nl2br($stage["message"]) . "</td>\n";
	$html .= " </tr>\n";
  }
  
  $html .= "</table>\n";
  $html .= "</div>\n";
} else {
  $html .= "Le cycle de vie est vide pour cette transaction.\n";
}


$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();

?>
