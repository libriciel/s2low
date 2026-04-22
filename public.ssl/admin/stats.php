<?php

use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\MenuHTML;
use S2lowLegacy\Class\PagerHTML;
use S2lowLegacy\Lib\FancyDate;
use S2lowLegacy\Model\HeliosTransactionsSQL;

/** @var Initialisation $initialisation */
/** @var ActesTransactionsSQL $actesTransactionsSQL */
/** @var HeliosTransactionsSQL $heliosTransactionsSQL */

[$initialisation,$actesTransactionsSQL,$heliosTransactionsSQL ] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class,ActesTransactionsSQL::class,HeliosTransactionsSQL::class]);

$initData = $initialisation->doInit();
$moduleData = $initialisation->initModule($initData, Initialisation::MODULENAMEHELIOS);

if ($initData->userInfo['role'] != 'SADM') {
    $_SESSION['error'] = 'Super admin only !';
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('connexion-status'));
    exit();
}

//nombre de transaction/mois

$nb_transactions_actes_list = $actesTransactionsSQL->getNbTransactionByMonth();

$nb_transactions_helios_list = $heliosTransactionsSQL->getNbTransactionByMonth();



$fancyDate = new FancyDate();


$menuHTML = new MenuHTML();
$pagerHTML  = new PagerHTML();

$doc = new HTMLLayout();

$doc->setTitle("Console d'administration");

$doc->openContainer();

$doc->openSideBar();
$doc->addBody($menuHTML->getMenuContent($initData->userInfo, $moduleData->modulesInfo));
$doc->closeSideBar();


$doc->openContent();

ob_start();
?>
    <div id="content">
        <h1>Statistiques (super admin)</h1>

        <div class="alert alert-warning">
            Attention, cette page n'est pas optimisée et ralentit l'ensemble de la plateforme.
            Merci d'utiliser avec la plus grande parcimonie pour les besoins du service.
        </div>

        <h2 id="desc_actes">Actes</h2>
        <table  class="data-table table table-striped " aria-describedby="desc_actes">
            <tr>
                <th scope="col">Mois</th>
                <th scope="col">Nombre de transactions</th>
            </tr>
            <?php foreach ($nb_transactions_actes_list as $nb_transaction_info) : ?>
                <tr>
                    <td><?php echo $fancyDate->getMois($nb_transaction_info['month'])?></td>
                    <td><?php echo $nb_transaction_info['nb']?></td>
                </tr>
            <?php endforeach ?>
        </table>

        <h2 id="desc_helios">Hélios</h2>
        <table  class="data-table table table-striped " aria-describedby="desc_helios">
            <tr>
                <th scope="col">Mois</th>
                <th scope="col">Nombre de transactions</th>
            </tr>
            <?php foreach ($nb_transactions_helios_list as $nb_transaction_info) : ?>
                <tr>
                    <td><?php echo $fancyDate->getMois($nb_transaction_info['month'])?></td>
                    <td><?php echo $nb_transaction_info['nb']?></td>
                </tr>
            <?php endforeach ?>
        </table>

    </div>


<?php
$html = ob_get_contents();
ob_end_clean();
$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();
$doc->buildFooter();
$doc->display();
