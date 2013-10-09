<?php
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');
require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransactionWorkflow.class.php');

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

$serviceUser = new ServiceUser(DatabasePool::getInstance());
$permission = new ModulePermission($serviceUser,"helios");

if ( ! $permission->canView($me,$owner)){
	$_SESSION["error"] = "Accès refusé";
	header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
	exit ();
}

$doc = new HTMLLayout();

$doc->setTitle("Helios : visualisation de transactions pour un fichier");
$doc->addBody("<div class=\"container\"><div class=\"row\">");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$currentStatus=HeliosTransactionWorkflow::getCurrentStatus($id);

$user_id = $trans->get('user_id');
$userInfo = new User($user_id);
$userInfo->init();

$authorityInfo = new Authority($userInfo->get("authority_id"));
$authorityInfo->init();


$html = "<p id=\"back-transaction-btn\"><a href=\"" . WEBSITE_SSL . "/modules/helios/\" class=\"btn btn-default\">Retour liste transactions</a></p>\n";
$html .= "<h2>Visualisation de transactions d'un fichier</h2>\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data table table-bordered\">\n";
$html .= $doc->getHTMLArrayline("Fichier", $trans->getFilenameForID($id)); 
$html .= $doc->getHTMLArrayline("Date de postage" ,Helpers :: getDateFromBDDDate(HeliosTransactionWorkflow::getDatePoste($id), true));
$html .= $doc->getHTMLArrayline("État actuel" ,$currentStatus);
$html .= $doc->getHTMLArrayline("Taille (octets)" ,$trans->get("file_size"));
$html .= $doc->getHTMLArrayline("Empreinte SHA1" ,$trans->get("sha1"));
$html .= $doc->getHTMLArrayline("Suivie par" ,$userInfo->getPrettyName());
$html .= $doc->getHTMLArrayline("Collectivité" ,$authorityInfo->get("name"));

$url = "Non définie";
$html .= $doc->getHTMLArrayline("URL d'archivage", $url);
$html .= "</table>\n";
$html .= "</div>\n";


$html .= "<h2>Récuperation du fichier posté ";
$html .= "<a href=\"" .WEBSITE_SSL. "/modules/helios/helios_download_file.php?id=" .$id. "\" title=\"Télécharger le fichier\">".$trans->getFilenameForID($id)."</a> </h2>";

// Affichage du Workflow

$workflow = $trans->fetchWorkflow();
$status = HeliosTransaction::getStatusList();

$html .= "<h2>Cycle de vie de la transaction</h2>\n";

if (count($workflow) > 0) {
  $html .= "<table class=\"data_table table table-striped\">\n";
  $html .= " <thead>\n";
  $html .= " <tr>\n";
  $html .= "  <th id=\"status\">Etat</th>\n";
  $html .= "  <th id=\"date\">Date</th>\n";
  $html .= "  <th id=\"message\">Message</th>\n";
  $html .= " </tr>\n";
  $html .= " </thead>\n";
  $html .= " <tbody>\n";
  foreach ($workflow as $stage) {
	$html .= " <tr>\n";
	$html .= "  <td headers=\"status\">" . $status[$stage["status_id"]] ;
  	if($stage["status_id"] == 4 || $stage["status_id"] == 6  || $stage["status_id"] == 8)
       $html .= " <a href=\"" .WEBSITE_SSL. "/modules/helios/helios_download_acquit.php?id=" .$id. "\" title=\"Télécharger l'acquittement\">voir</a> </h3>";
    $html .= "</td>\n";
	$html .= "  <td headers=\"date\">" . Helpers::getDateFromBDDDate($stage["date"], true) . "</td>\n";
	$html .= "  <td headers=\"message\" class=\"long_field\">" . nl2br($stage["message"]) . "</td>\n";
	$html .= " </tr>\n";
  }
  $html .= " </tbody>\n";
  $html .= "</table>\n";
} else {
  $html .= "Le cycle de vie est vide pour cette transaction.\n";
}


$html .= "</div>\n";

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();

?>
