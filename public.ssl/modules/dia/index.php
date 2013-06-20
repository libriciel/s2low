<?php
require_once( __DIR__ . "/../../../init/init-www-dia.php");

$recuperateur = new Recuperateur($_GET);

$authority_filtre =  $recuperateur->get("authority");
$filename = $recuperateur->get("filename");
$fstatus =  $recuperateur->get("status",0);
$fmin_submission_date =  $recuperateur->get("min_submission_date");
$fmax_submission_date =  $recuperateur->get("max_submission_date");
$sortWay =   $recuperateur->get("sortway","desc");
$order = $recuperateur->get('order','id');
$page_number = $recuperateur->getInt('page',1);
$taille_page =  $recuperateur->getInt('count',10);

$transactionDIA = new TransactionDIA($sqlQuery);

if ($droit->isSuperAdmin($userInfo) ) { 
	$transactionDIA->setAuthority($authority_filtre);
}elseif ($droit->isAdmin($userInfo)){
	$transactionDIA->setAuthority($userInfo['authority_id']);
} else {
  $serviceUser = new ServiceUser(DatabasePool::getInstance());
  $collegues = $serviceUser->getMesCollegues($connexion->getId());
  $collegue[] = $connexion->getId();
  foreach($collegues as $info){
  	$collegue[] =  $info['id_user'];
  }
  $transactionDIA->setUserId($collegue);
}

$transactionDIA->setStatus($fstatus);
$transactionDIA->setDateMinSubmission($fmin_submission_date);
$transactionDIA->setDateMaxSubmission($fmax_submission_date);
$transactionDIA->setFilename($filename);
$transactionDIA->setOrder($order,$sortWay);
$transactionDIA->setPageNumber($page_number,$taille_page);

$envelopes = $transactionDIA->getAll();
$nb_transactions = $transactionDIA->getNbTransaction();

$status = $transactionDIA->getStatus();
$status[0] = "Tous les états";

$menuHTML = new MenuHTML();
$pagerHTML  = new PagerHTML();
$fancyDate = new FancyDate();
$listeDIAHTML = new ListeDIAHTML();

if ($droit->isSuperAdmin($userInfo)){
	$listeDIAHTML->addCollectivite($authoritySQL->getAll(),$authority_filtre);
} 

$listeDIAHTML->setCritere($status,$fstatus,$filename);
$listeDIAHTML->setDate($fmin_submission_date,$fmax_submission_date);


$doc = new HTMLLayout();
$doc->setTitle("Liste des transactions - DIA - S²low");
$doc->addCSS("/custom/styles/date-picker.css");
$doc->addJavascript("/javascript/date-picker.js");
$doc->addJavascript("/javascript/tedetis.js");

$doc->addBody($menuHTML->getMenu($userInfo,$modulesInfo));
$doc->addBody($pagerHTML->getHTML($page_number,$nb_transactions,$taille_page));

ob_start();
?>
<div id="content">
	<h1>DIA - Déclaration d'intention d'aliéner</h1>
<?php 
$listeDIAHTML->display($envelopes);
?>	
</div>
<?php 			
$html = ob_get_contents();
ob_end_clean();

$doc->addBody($html);
$doc->buildFooter();
$doc->display();