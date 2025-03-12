<?php

use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\HTMLLayoutFactory;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\MenuHTML;
use S2lowLegacy\Class\PagerHTML;
use S2lowLegacy\Lib\SQLQuery;

/** @var Initialisation $initialisation */
/** @var SQLQuery $sqlQuery */
/** @var HTMLLayoutFactory $HTMLLayoutFactory */

[$initialisation,$sqlQuery, $HTMLLayoutFactory ] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class,SQLQuery::class, HTMLLayoutFactory::class]);

$initData = $initialisation->doInit();
$moduleData = $initialisation->initModule($initData, Initialisation::MODULENAMEHELIOS);

if ($initData->userInfo['role'] != 'SADM') {
    $_SESSION['error'] = 'Super admin only !';
    header('Location: ' . WEBSITE);
    exit();
}


$info = [
    1 => 'Posté',2 => 'En attente de transmission',3 => 'Transmis'
];

$result = [];
foreach ($info as $status_id => $status_libelle) {
    $sql = 'SELECT count(*) FROM helios_transactions WHERE last_status_id=?';
    $result[$status_id] = $sqlQuery->queryOne($sql, $status_id);
}

$sql = 'SELECT count(*) FROM helios_transactions WHERE last_status_id=3';




$menuHTML = new MenuHTML();
$pagerHTML  = new PagerHTML();

$doc = $HTMLLayoutFactory->createHTMLLayout();

$doc->setTitle('Tedetis : module helios statistique');

$doc->openContainer();

$doc->openSideBar();
$doc->addBody($menuHTML->getMenuContent($initData->userInfo, $moduleData->modulesInfo));

$doc->closeSideBar();


$doc->openContent();

ob_start();
?>
<div id="content">
    <h1>Helios : status en cours DGFIP</h1>

    <h2 id="list_desc">Liste des status</h2>
<table  class="data-table table table-striped " aria-describedby="list_desc">
    <tr>
        <th scope="col">Status</th>
        <th scope="col">Nombre de transactions</th>
    </tr>
    <?php foreach ($result as $status_id => $nb) : ?>
        <tr>
            <td><?php echo $info[$status_id]?></td>
            <td><?php echo $nb?></td>
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
