<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\MenuHTML;
use S2lowLegacy\Class\User;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Lib\SQLQuery;

/** @var Initialisation $initialisation */
/** @var UserContext $userContext */
[$initialisation, $userContext ] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, UserContext::class]);

if (! $userContext->me->isSuper()) {
    $_SESSION['error'] = 'Accès refusé';
    header('Location: ' . WEBSITE_SSL);
    exit();
}


$recuperateur = new Recuperateur($_GET);
$type = $recuperateur->get('type');

if (! in_array($type, ['extended','rgs'])) {
    $type = 'extended';
}



if ($type == 'rgs') {
    $certificate_list = glob(RGS_VALIDCA_PATH . '/*.pem');
} else {
    $certificate_list = glob(EXTENDED_VALIDCA_PATH . '/*.pem');
}

$menuHTML = new MenuHTML();

$doc = new HTMLLayout();
$doc->setTitle('Liste des certificats - S²low');

$doc->openContainer();
$doc->openSideBar();
$doc->addBody($menuHTML->getMenuContent($userContext->userInfo, []));
$doc->closeSideBar();
$doc->openContent();

ob_start();
?>
    <h1>Autorités de certification</h1>



    <div id="actions_area">
        <h2>Actions</h2>
        <a class="btn btn-primary" href="/admin/utilities/certificate_list.php?type=extended">
            Voir les certificats étendus
        </a>
        <a class="btn btn-primary" href="/admin/utilities/certificate_list.php?type=rgs">Voir les certificats RGS</a>
        <a class="btn btn-primary" href="/admin/utilities/test-certificate.php">Tester un certificat</a>
    </div>


<h2>Liste des certificats <?php echo $type == 'rgs' ? 'RGS' : 'étendus' ?></h2>

<table class="data-table table table-striped " role="presentation">
<?php foreach ($certificate_list as $i => $cert) : ?>
    <tr>
        <td>
            <a href="/admin/utilities/certificate.php?type=<?php echo $type ?>&name=<?php echo basename($cert) ?>">
                <?php echo basename($cert) ?>
            </a>
        </td>
    </tr>
<?php endforeach ?>
</table>


<?php
$html = ob_get_contents();
ob_end_clean();

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();
$doc->display();