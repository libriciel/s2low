<?php

use S2lowLegacy\Class\actes\ListeActesHTML;
use S2lowLegacy\Class\actes\TransactionSQL;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Droit;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\MenuHTML;
use S2lowLegacy\Class\PagerHTML;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Lib\FancyDate;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Model\AuthoritySQL;

/** @var Initialisation $initialisation */
/** @var Droit $droit */
/** @var TransactionSQL $transactionSQL */
/** @var \S2lowLegacy\Model\AuthoritySQL $authoritySQL */

[$initialisation, $droit,  $transactionSQL,$authoritySQL] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, Droit::class, TransactionSQL::class, AuthoritySQL::class]);

$initData = $initialisation->doInit();
$moduleData = $initialisation->initModule($initData, Initialisation::MODULENAMEACTES, Initialisation::DROITSACTES);

$recuperateur = new Recuperateur($_GET);

$authority_filtre =  $recuperateur->get('authority');
$fnature =  $recuperateur->get('nature');
$ftype =  $recuperateur->get('type');
$fnum =  $recuperateur->get('num');
$objet = $recuperateur->get('objet');

if (isset($_GET['status']) && $_GET['status'] === '0') {
    $fstatus = 0;
} else {
    $fstatus =  $recuperateur->get('status', 'all');
}

if ($fstatus != TransactionSQL::EN_COURS && $fstatus != 'all' && ! is_numeric($fstatus)) {
    $fstatus = TransactionSQL::EN_COURS;
}

$fmin_submission_date =  $recuperateur->get('min_submission_date');
$fmax_submission_date =  $recuperateur->get('max_submission_date');
$fmin_ack_date = $recuperateur->get('min_ack_date');
$fmax_ack_date =  $recuperateur->get('max_ack_date');

$sortWay =   $recuperateur->get('sortway', 'desc');
$order = $recuperateur->get('order', 'id');
$page_number = $recuperateur->getInt('page', 1);
$taille_page =  $recuperateur->getInt('count', 10);

if ($ftype != '0' && empty($ftype)) {
    $ftype = '1';
}

if ($droit->isSuperAdmin($initData->userInfo)) {
    $transactionSQL->setAuthority($authority_filtre);
} elseif ($droit->isAdmin($initData->userInfo)) {
    $transactionSQL->setAuthority($initData->userInfo['authority_id']);
} else {
    $serviceUser = new ServiceUser(DatabasePool::getInstance());
    $collegues = $serviceUser->getMesCollegues($initData->connexion->getId());
    $collegue[] = $initData->connexion->getId();
    foreach ($collegues as $info) {
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
$transactionSQL->setOrder($order, $sortWay);
$transactionSQL->setPageNumber($page_number, $taille_page);

$envelopes = $transactionSQL->getAll();

if ($droit->isSuperAdmin($initData->userInfo)) {
    $nb_transactions = ($page_number + 10) * $taille_page;
} else {
    $nb_transactions = $transactionSQL->getNbTransaction();
}

$transTypes = $transactionSQL->getTypes();
$transTypes['all'] = 'Tous les types';

$transNatures = $transactionSQL->getNatures();


$status = $transactionSQL->getStatus();
$status[TransactionSQL::EN_COURS] = 'En cours';
$status['all'] = 'Tous les états';

$menuHTML = new MenuHTML();
$pagerHTML  = new PagerHTML();
$fancyDate = new FancyDate();
$listeActesHTML = new ListeActesHTML();

if ($droit->isSuperAdmin($initData->userInfo)) {
    $listeActesHTML->addCollectivite($authoritySQL->getAll(), $authority_filtre);
} elseif (! $droit->isGroupAdmin($initData->userInfo) && ($moduleData->permUser == 'RW' || $moduleData->permUser == 'CS')) {
        $listeActesHTML->addActionBox();
}

$listeActesHTML->setCritere($transTypes, $ftype, $transNatures, $fnature, $status, $fstatus, $fnum, $objet);
$listeActesHTML->setDate($fmin_submission_date, $fmin_ack_date, $fmax_submission_date, $fmax_ack_date, $fancyDate);

$doc = new HTMLLayout();
$doc->setTitle('Liste des transactions - ACTES - S²low');
$doc->addHeader(
    "<script type=\"text/javascript\" src=\"" . Helpers::getLink('/jsmodules/jquery.js') . "\">" .
    '</script>'
);
$doc->addHeader(
    "<script type=\"text/javascript\" src=\"" . Helpers::getLink('/jsmodules/jqueryui.js') . "\">" .
    '</script>'
);
$doc->addJavascript('/javascript/tedetis.js');

$doc->openContainer();
$doc->openSideBar();
$doc->addBody($menuHTML->getMenuContent($initData->userInfo, $moduleData->modulesInfo));
$doc->addBody($pagerHTML->getHTML($page_number, $nb_transactions, $taille_page));
$doc->closeSideBar();
$doc->openContent();

ob_start();
?>
        <h1>ACTES - Dématérialisation du contrôle de légalité</h1>
        <?php $listeActesHTML->display($envelopes);?>   
<?php
$html = ob_get_contents();
ob_end_clean();

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();
$doc->display();
