<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\HTMLLayoutFactory;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\MenuHTML;
use S2lowLegacy\Class\PagerHTML;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Lib\SQLQuery;

/** @var Initialisation $initialisation */
/** @var SQLQuery $sqlQuery */
/** @var \S2lowLegacy\Class\UserContext $userContext */
/** @var HTMLLayoutFactory $htmlLayoutFactory */
[$initialisation,$sqlQuery, $userContext, $htmlLayoutFactory] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class,SQLQuery::class, UserContext::class, HTMLLayoutFactory::class]);

$moduleData = $initialisation->initModule($userContext, Initialisation::MODULENAMEHELIOS);

if ($userContext->userInfo['role'] != 'SADM') {
    $_SESSION['error'] = 'Super admin only !';
    header('Location: ' . Helpers::getLink('connexion-status'));
    exit();
}


$sql = ' SELECT DISTINCT users.email,authorities.name,authority_groups.name as group_name from users ' .
    ' JOIN users_perms ON users_perms.user_id=users.id ' .
    " JOIN modules ON users_perms.module_id=modules.id AND modules.name='actes' " .
    "AND (users_perms.perm='RO' OR users_perms.perm='RW')" .
    ' JOIN authorities ON authorities.id=users.authority_id ' .
    ' JOIN authority_groups ON authorities.authority_group_id = authority_groups.id ' .
    ' JOIN modules_authorities ON authorities.id=modules_authorities.authority_id ' .
    " JOIN modules m2 ON modules_authorities.module_id=m2.id AND m2.name='actes'" .
    ' WHERE users.status = 1 ' .
    'ORDER BY authority_groups.name, authorities.name, users.email';


$user_list = $sqlQuery->query($sql);


$csv = isset($_GET['csv']) && $_GET['csv'];

if ($csv) {
    header('Content-type: text/csv; charset=iso-8859-1');
    header('Content-disposition: attachment; filename=ancien-system-notif.csv');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0,pre-check=0');
    header('Pragma: public');

    $out = fopen('php://output', 'w');
    foreach ($user_list as $user_info) {
        fputcsv($out, $user_info);
    }
    fclose($out);

    exit;
}

$pagerHTML  = new PagerHTML();

$doc = $htmlLayoutFactory->createLayout();

$doc->setTitle("Console d'administration");

$doc->openContainer();

$doc->openSideBar();
$doc->buildMenu();
$doc->closeSideBar();


$doc->openContent();

ob_start();
?>
    <div id="content">
        <h1>Client qui n'ont pas de SAE</h1>

    <div id="table_content_desc" class="alert alert-info">
        Sur cette page, on ne présente que les collectivités qui n'ont pas de SAE et qui ont des actes de plus
        de 26 mois
    </div>
<p>
    <a href="/admin/ancien_systeme_notif.php?csv=true" class="btn btn-primary">CSV</a>
</p>
<table class="data-table table table-striped" aria-describedby="table_content_desc">
    <tr>
        <th scope="col">Collectivité</th>
        <th scope="col">Groupes</th>
        <th scope="col">Email</th>
    </tr>
    <?php foreach ($user_list as $user_info) : ?>
        <tr>
            <td><?php hecho($user_info['name'])?></td>
            <td><?php hecho($user_info['group_name'])?></td>
            <td><?php echo $user_info['email'] ?></td>
        </tr>
    <?php endforeach; ?>

</table>

<?php
$html = ob_get_contents();
ob_end_clean();
$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();
$doc->buildFooter();
$doc->display();
