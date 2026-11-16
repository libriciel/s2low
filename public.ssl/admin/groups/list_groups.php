<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\MenuHTML;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\GroupSQL;

/** @var Initialisation $initialisation */
/** @var SQLQuery $sqlQuery */
[$initialisation, $sqlQuery] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, SQLQuery::class]);

$initData = $initialisation->doInit();

$me = new User();

if (! $me->authenticate()) {
    $_SESSION['error'] = "Echec de l'authentification";
    header('Location: ' . \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink('connexion-status'));
    exit();
}

if (! $me->isSuper()) {
    $_SESSION['error'] = 'Accès refusé';
    header('Location: ' . WEBSITE_SSL);
    exit();
}


$groupSQL = new GroupSQL($sqlQuery);
$authorityCountPerGroup = $groupSQL->countAuthoritiesPerGroup();
$authorityCountWithoutGroup = $groupSQL->countAuthoritiesWithoutGroup();


$menuHTML = new MenuHTML();

$doc = new HTMLLayout();
$doc->setTitle('Configuration de la connexion SAE - S²low');
$doc->openContainer();
$doc->openSideBar();
$doc->addBody($menuHTML->getMenuContent($initData->userInfo, []));
$doc->closeSideBar();
$doc->openContent();

ob_start();
?>

    <h1 id="groupes_col_desc">Groupes de collectivités</h1>
    <p id="back-transaction-btn">
        <a href="<?php echo \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink('/admin/groups/admin_groups.php'); ?>" class="btn btn-default">Retour liste groupes</a>
    </p>

    <table class="data-table table table-striped " aria-describedby="groupes_col_desc">
        <tr>
            <th scope="col">Groupe</th>
            <th scope="col">Nombre de collectivités</th>

        </tr>
        <?php foreach ($authorityCountPerGroup as $groupCount) : ?>
            <tr>
                <td><a href="/admin/groups/admin_group_edit.php?id=<?php echo $groupCount->groupId ?>">
                        <?php hecho($groupCount->groupName)  ?></a>
                </td>
                <td>
                    <?php echo $groupCount->authorityCount ?>
                </td>
            </tr>
        <?php endforeach ?>
        <?php if ($authorityCountWithoutGroup > 0) : ?>
            <tr>
                <td>
                    <?php hecho('Collectivité(s) sans groupe attaché')  ?>
                </td>
                <td>
                    <?php echo $authorityCountWithoutGroup ?>
                </td>
            </tr>
        <?php endif; ?>

    </table>
<?php
$html = ob_get_contents();
ob_end_clean();

$doc->addBody($html);
$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();
$doc->display();

