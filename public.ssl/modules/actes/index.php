<?php
require_once( __DIR__ . "/../../../init/init-www-actes.php");
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');

$recuperateur = new Recuperateur($_GET);

$authority_filtre =  $recuperateur->get("authority");
$fnature =  $recuperateur->get("nature");
$ftype =  $recuperateur->get("type");
$fnum =  $recuperateur->get("num");
$objet = $recuperateur->get("objet");

if (isset( $_GET['status']) && $_GET['status'] === '0'){
	$fstatus = 0;
} else {
	$fstatus =  $recuperateur->get("status",10);
}
# Le statut par défaut est "En cours" (10)
if ($fstatus != "10" && $fstatus != "all" && ! is_numeric($fstatus)) {
  $fstatus = "10";
}

$fmin_submission_date =  $recuperateur->get("min_submission_date");
$fmax_submission_date =  $recuperateur->get("max_submission_date");
$fmin_ack_date = $recuperateur->get("min_ack_date");
$fmax_ack_date =  $recuperateur->get("max_ack_date");

$sortWay =   $recuperateur->get("sortway","desc");
$order = $recuperateur->get('order','id');
$page_number = $recuperateur->getInt('page',1);
$taille_page =  $recuperateur->getInt('count',10);

if ($ftype != "0" && empty($ftype)) {
  $ftype = "1";
}

$transactionSQL = new TransactionSQL($sqlQuery);
if ($droit->isSuperAdmin($userInfo) ) { 
	$transactionSQL->setAuthority($authority_filtre);
}elseif ($droit->isAdmin($userInfo)){
	$transactionSQL->setAuthority($userInfo['authority_id']);
} else {
  $serviceUser = new ServiceUser(DatabasePool::getInstance());
  $collegues = $serviceUser->getMesCollegues($connexion->getId());
  $collegue[] = $connexion->getId();
  foreach($collegues as $info){
  	$collegue[] =  $info['id_user'];
  }
  $transactionSQL->setUserId($collegue);
}

$transactionSQL->setNature($fnature);
$transactionSQL->setType($ftype);
$transactionSQL->setStatus($fstatus);
$transactionSQL->setNumero($fnum);
$transactionSQL->setDateMinSubmission($fmin_submission_date);
$transactionSQL->setDateMaxSubmission($fmax_submission_date);
$transactionSQL->setDateMinAck($fmin_ack_date);
$transactionSQL->setDateMaxAck($fmax_ack_date);
$transactionSQL->setObjet($objet);
$transactionSQL->setOrder($order,$sortWay);
$transactionSQL->setPageNumber($page_number,$taille_page);

$envelopes = $transactionSQL->getAll();


$nb_transactions = $transactionSQL->getNbTransaction();

$transTypes = $transactionSQL->getTypes();
$transTypes["0"] = "Tous les types";

$transNatures = $transactionSQL->getNatures();


$status = $transactionSQL->getStatus();
$status["10"] = "En cours";
$status["all"] = "Tous les états";

$menuHTML = new MenuHTML();
$pagerHTML  = new PagerHTML();
$fancyDate = new FancyDate();
$listeActesHTML = new ListeActesHTML();

if ($droit->isSuperAdmin($userInfo)){
	$listeActesHTML->addCollectivite($authoritySQL->getAll(),$authority_filtre);
} elseif($permUser == 'RW') {
 	$listeActesHTML->addActionBox();
}

$listeActesHTML->setCritere($transTypes,$ftype,$transNatures, $fnature,$status, $fstatus,$fnum,$objet);
$listeActesHTML->setDate($fmin_submission_date,$fmin_ack_date,$fmax_submission_date,$fmax_ack_date);


$doc = new HTMLLayout();
$doc->setTitle("Liste des transactions - ACTES - S²low");
$doc->addCSS("/custom/styles/date-picker.css");
$doc->addJavascript("/javascript/date-picker.js");
$doc->addJavascript("/javascript/tedetis.js");

$doc->addBody($menuHTML->getMenu($userInfo,$modulesInfo));
$doc->addBody($pagerHTML->getHTML($page_number,$nb_transactions,$taille_page));

ob_start();
?>
<div id="content">
	<h1>ACTES - Dématèrialisation du contrôle de légalité</h1>
<?php 
$listeActesHTML->display($envelopes);
?>	
</div>
<?php 			
$html = ob_get_contents();
ob_end_clean();

$doc->addBody($html);
$doc->buildFooter();
$doc->display();