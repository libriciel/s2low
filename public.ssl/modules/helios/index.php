<?php

use S2low\DTO\TransactionList\HeliosTransactionTable;
use S2low\DTO\TransactionList\TransactionTableSorting;
use S2low\Services\TransactionList\HeliosTransactionRowFactory;
use S2low\Services\TransactionList\TransactionTableRenderer;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\DatePicker;
use S2lowLegacy\Class\Droit;
use S2lowLegacy\Class\helios\HeliosTransactionsListe;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\MenuHTML;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\PagerHTML;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Model\AuthoritySQL;

/** @var Initialisation $initialisation */
/** @var Droit $droit */
/** @var HeliosTransactionsListe $heliosTransactionsListe */
/** @var AuthoritySQL $authoritySQL */
/** @var HeliosTransactionRowFactory $heliosTransactionRowFactory */
/** @var TransactionTableRenderer $transactionTableRenderer */

[$initialisation, $droit,$heliosTransactionsListe,$authoritySQL, $heliosTransactionRowFactory, $transactionTableRenderer] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, Droit::class, HeliosTransactionsListe::class,AuthoritySQL::class, HeliosTransactionRowFactory::class, TransactionTableRenderer::class]);

$initData = $initialisation->doInit();
$moduleData = $initialisation->initModule($initData, Initialisation::MODULENAMEHELIOS);

$recuperateur = new Recuperateur($_GET);


$sortWay =   $recuperateur->get('sortway', 'desc');
$order = $recuperateur->get('order', 'id');
$page_number = $recuperateur->getInt('page', 1);
$taille_page =  $recuperateur->getInt('count', 10);


$fmin_submission_date =  $recuperateur->get('min_submission_date');
$fmax_submission_date =  $recuperateur->get('max_submission_date');
$fmin_ack_date = $recuperateur->get('min_ack_date');
$fmax_ack_date =  $recuperateur->get('max_ack_date');


if (isset($_GET['status']) && $_GET['status'] === '0') {
    $fstatus = 0;
} else {
    $fstatus =  $recuperateur->get('status', 'all');
}

$fauthority = $recuperateur->get('authority');
$fnum =  $recuperateur->get('num');
$fnomFic = $recuperateur->get('nomFic');



if ($droit->isSuperAdmin($initData->userInfo)) {
    $heliosTransactionsListe->setAuthority($fauthority);
} elseif ($droit->isGroupAdmin($initData->userInfo) && $fauthority) {
    $authorityFiltreInfo = $authoritySQL->getInfo($fauthority);

    if ($droit->administersHelios($initData->userInfo, $authorityFiltreInfo)) {
        $heliosTransactionsListe->setAuthority($fauthority);
    } else {
        $heliosTransactionsListe->setAuthority($initData->userInfo['authority_id']);
    }
} elseif ($droit->isAnyAdmin($initData->userInfo)) {
    $heliosTransactionsListe->setAuthority($initData->userInfo['authority_id']);
} else {
    $serviceUser = new ServiceUser(DatabasePool::getInstance());
    $collegues = $serviceUser->getMesCollegues($initData->connexion->getId());
    $collegue[] = $initData->connexion->getId();
    foreach ($collegues as $info) {
        $collegue[] =  $info['id_user'];
    }
    $heliosTransactionsListe->setUserId($collegue);
}
$heliosTransactionsListe->setOrder($order, $sortWay);
$heliosTransactionsListe->setPageNumber($page_number, $taille_page);
$heliosTransactionsListe->setDateMinSubmission($fmin_submission_date);
$heliosTransactionsListe->setDateMaxSubmission($fmax_submission_date);
$heliosTransactionsListe->setDateMinAck($fmin_ack_date);
$heliosTransactionsListe->setDateMaxAck($fmax_ack_date);
$heliosTransactionsListe->setStatus($fstatus);
$heliosTransactionsListe->setObjet($fnum);
$heliosTransactionsListe->setNomFic($fnomFic);


//if ($droit->isSuperAdmin($userInfo)) {
//On empeche le comptage des transactions pour tout le monde (c'est trop long)
    $nb_transactions = ($page_number + 10) * $taille_page;
/*} else {
    $nb_transactions = $heliosTransactionsListe->getNbTransaction();
}*/



// Instanciation du module courant
$module = new Module();
if (!$module->initByName('helios')) {
    $_SESSION['error'] = "Erreur d'initialisation du module";
    header('Location: ' . WEBSITE_SSL);
    exit();
}

$me = new User();

if (!$me->authenticate()) {
    $_SESSION['error'] = "Echec de l'authentification";
    header('Location: ' . Helpers::getLink('connexion-status'));
    exit();
}

if (!$module->isActive() || !$me->canAccess($module->get('name'))) {
    $_SESSION['error'] = 'Accés refusé';
    header('Location: ' . WEBSITE_SSL);
    exit();
}


$helios_configured = $initData->authorityInfo['helios_ftp_dest'];


$envelopes = $heliosTransactionsListe->getAll();


$js = <<<EOJS
<script type="text/javascript">
//<![CDATA[
function show_all() {
  toggle_all("block", "-");
}

function hide_all() {
  toggle_all("none", "+");
}

function toggle_all(style, symbol) {
  done = false;
  i = 0;

  while (! done) {
	var content = document.getElementById("envelope_content_" + i);
	var expander = document.getElementById("expander_" + i);

	if (content && expander) {
	  content.style.display = style;
	  expander.innerHTML = symbol;
	} else {
	  done = true;
	}
	i++;
  }
}

function toggle_envelope_content(id) {
  var content = document.getElementById("envelope_content_" + id);
  var expander = document.getElementById("expander_" + id);

  if (content.style.display == "block") {
	content.style.display = "none";
	expander.innerHTML = "+";
  } else {
	content.style.display = "block";
	expander.innerHTML = "-";
  }  
}

function GereChkbox(conteneur, a_faire) {
  var blnEtat=null;
  var Tab = document.getElementsByTagName("input");
  for(var i = 0; i < Tab.length; i++){  
	Chckbox=Tab[i];
	if (Chckbox.getAttribute("type")=="checkbox") {
		blnEtat = (a_faire=='0') ? false : (a_faire=='1') ? true : (Chckbox.checked) ? false : true;
		Chckbox.checked=blnEtat;
	}
  }
}

function afficheWarning(){
  var n = 0;
  var liste = document.getElementsByTagName("input");
  for(var i = 0; i < liste.length; i++){
    if (liste[i].getAttribute("type")=="checkbox"){
      if(liste[i].checked) n++;
    }
  }
  var msg = "Voulez-vous vraiment affecter les " + n + " transactions sélectionnées ? ";
  msg += "Cette action est non réversible et est sous votre entière responsabilité";
  return confirm(msg);
}
//]]>
</script>
EOJS;


$menuHTML = new MenuHTML();
$pagerHTML  = new PagerHTML();

$doc = new HTMLLayout();
$doc->addHeader($js);

$doc->addHeader("<script src=\"" . Helpers::getLink('/jsmodules/jquery.js') . "\" type=\"text/javascript\"></script>\n");
$doc->addHeader("<script type=\"text/javascript\" src=\"" . Helpers::getLink('/jsmodules/jqueryui.js') . "\"></script>");
$doc->setTitle('Tedetis : module helios');
$doc->addCSS(Helpers::getLink('/custom/styles/transaction_list.css'));

$doc->openContainer();

$doc->openSideBar();
$doc->addBody($menuHTML->getMenuContent($initData->userInfo, $moduleData->modulesInfo));
$doc->addBody($pagerHTML->getHTML($page_number, $nb_transactions, $taille_page));

$doc->closeSideBar();


$doc->openContent();


$statusLabels = HeliosTransaction::getStatusList();
$status = $statusLabels;
$status['999'] = 'En cours';
$status['all'] = 'Tous les états';

if ($envelopes) {
    $owner = new User($envelopes[0]['user_id']);
    $owner->init();
}

$sortWay = (isset($_GET['sortway']) && ($_GET['sortway'] == 'asc')) ? 'desc' : 'asc';

$heliosTable = new HeliosTransactionTable(
    array_map(
        static fn(array $line) => $heliosTransactionRowFactory->fromListLine($line, $statusLabels),
        $envelopes
    ),
    TransactionTableSorting::fromQuery(Helpers::getLink('/modules/helios/index.php'), $_GET),
    $me->isGroupAdminOrSuper()
);


ob_start();
?>
<script type="text/javascript" src="<?php echo Helpers::getLink('/jsmodules/jquery.js')?>"></script>
<script type="text/javascript" src="<?php echo Helpers::getLink('/jsmodules/select2.js')?>"></script>
<script type="text/javascript" src="/javascript/zselect_s2low.js"></script>

<h1>Helios - Dématérialisation de documents financiers</h1>


<div id="actions_area">
    <h2>Actions</h2>

    <?php if (! $helios_configured) : ?>
        <div class='alert alert-danger'>Attention les paramètres du module Helios sont incomplets et ne permettront pas la télétransmission.</div>
    <?php endif; ?>

    <?php if (!$me->isSuper() && $me->canEdit($module->get('name'))) : ?>
        <a class="btn btn-primary" href="<?php echo Helpers::getLink('/modules/helios/helios_fichier_import.php'); ?>" >Importer un fichier</a>
    <?php endif; ?>
    <a class="btn btn-primary" href="<?php echo Helpers::getLink('/modules/helios/helios_retour.php'); ?>" title="afficher la liste des réponses reçues">Réponse d'Hélios</a>
</div>

<h2 class="toggle_title" onclick="javascript:toggle_visibility('filtering-area');">Filtrage</h2>
<div id="filtering-area">
    <form role="form" class="form-horizontal" action="<?php echo Helpers::getLink('/modules/helios/index.php'); ?>" method="get">
        <div class="form-group">
            <label class="col-md-3 control-label" for="status">État</label>
            <div class="col-md-3">
                <?php echo $doc->getHTMLSelect('status', $status, $fstatus)  ?>
            </div>
            <label class="col-md-3 control-label" for="filename-contain">Le nom de fichier contient</label>
            <div class="col-md-3">
                <input id="filename-contain" class="form-control" type="text" name="num" size="20" maxlength="25"
                    value="<?php hecho((mb_strlen($fnum) > 0) ? $fnum : '');?>" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-3" for="min_submission_date">Date de postage minimale</label>
            <div class="col-md-3">
                <?php echo (new DatePicker("min_submission_date", $fmin_submission_date))->show() ?>
                
            </div>
            <label class="col-md-3" for="min_ack_date">Date d'acquittement minimale</label>
            <div class="col-md-3">
                <?php echo (new DatePicker("min_ack_date", $fmin_ack_date))->show() ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-3" for="max_submission_date">Date de postage maximale</label>
            <div class="col-md-3">
                <?php echo (new DatePicker("max_submission_date", $fmax_submission_date))->show() ?>
            </div>
            <label class="col-md-3" for="max_ack_date">Date d'acquittement maximale</label>
            <div class="col-md-3">
                <?php echo (new DatePicker("max_ack_date", $fmax_ack_date))->show() ?>
            </div>
        </div>
        <div class="form-group">

            <?php if ($me->isGroupAdminOrSuper()) : ?>
                <label for="authority" class="col-md-3 control-label">Collectivité</label>
                <div class="col-md-3">
                    <select class="form-control zselect_authorities" name="authority">
                        <?php if ($me->isSuper()) :
                            ?><option value="">Toutes</option><?php
                        endif; ?>
                        <?php foreach ($me->getAllPossibleAuthority() as $key => $val) : ?>
                            <option value="<?php hecho($key) ?>"  <?php echo (strcmp($key, $fauthority) == 0) ? " selected='selected'" : ""; ?>>
                                <?php hecho($val)?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif;?>
            <label class="col-md-3 control-label" for="xml-nomfic-contain">La balise NomFic contient</label>
            <div class="col-md-3">
                <input id="xml-nomfic-contain" class="form-control" type="text" name="nomFic" size="20"
                    value="<?php hecho((mb_strlen($fnomFic) > 0) ? $fnomFic : ""); ?>" />
            </div>

        </div>
        <div class="form-group">
            <button type="submit" class="col-md-offset-3 col-md-3 btn btn-default">Filtrer</button>
            <a href="<?php echo Helpers::getLink("/modules/helios/index.php");?>" class="col-md-offset-3 col-md-3 btn btn-default">Remise à zéro</a>
        </div>
    </form>
</div>

<h2>Liste des fichiers postés</h2>
<div id="transaction-area">

<?php if (count($envelopes) <= 0) : ?>
    Pas de transaction trouvée correspondant aux critères de filtrage.
<?php else : ?>
    <form id="div_chck" onsubmit="return afficheWarning();" action="<?php echo Helpers::getLink("/modules/helios/helios_transac_close.php"); ?>" method="post">
        <?php echo $transactionTableRenderer->renderHelios($heliosTable) ?>
        <br/><br/>
        <div id="selection-actions">
            <a href="#tedetis" onclick="GereChkbox('div_chck','1');" title="Tout sélectionner" class="btn btn-default">Tout sélectionner</a>
            <a href="#tedetis" onclick="GereChkbox('div_chck','0');" title="Tout désélectionner" class="btn btn-default">Tout desélectionner</a>
            <a href="#tedetis" onclick="GereChkbox('div_chck','2');" title="Inverser la sélection" class="btn btn-default">Inverser la sélection</a>
        </div>
        <br/>

        <?php if ($heliosTable->hasSelectableRow()) : ?>
            <input type='submit' class='btn btn-default' value='Envoyer la sélection au SAE'/>
        <?php endif; ?>
    </form>


<?php endif; ?>
</div>
<?php

$html = ob_get_contents();
ob_end_clean();
$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();
$doc->buildFooter();
$doc->display();
