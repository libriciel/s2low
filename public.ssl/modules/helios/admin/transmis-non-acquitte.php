<?php

use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\MenuHTML;
use S2lowLegacy\Class\PagerHTML;
use S2lowLegacy\Model\HeliosTransactionsSQL;

require_once(__DIR__ . "/../../../../init/init-www-helios.php");

if ($userInfo['role'] != 'SADM') {
    $_SESSION["error"] = "Super admin only !";
    header("Location: " . WEBSITE);
    exit();
}


/** @var HeliosTransactionsSQL $heliosTransactionsSQL */
$heliosTransactionsSQL = $objectInstancier->get(HeliosTransactionsSQL::class);
$transactions_list = $heliosTransactionsSQL->getNonAcquitte();


$menuHTML = new MenuHTML();
$pagerHTML  = new PagerHTML();

$doc = new HTMLLayout();

$doc->setTitle("Console d'administration");

$doc->openContainer();

$doc->openSideBar();
$doc->addBody($menuHTML->getMenuContent($userInfo, $modulesInfo));

$doc->closeSideBar();


$doc->openContent();

ob_start();
?>
    <div id="content">
    <h1>Helios - Dématérialisation de documents financiers</h1>
    <p id="back-user-btn">
        <a href="/admin/index.php" class="btn btn-default" title="">
            Retour console d'administration
        </a>
    </p>

    <h2>Actions</h2>

    <a href="/modules/helios/admin/transmis-non-acquitte-by-mail.php" class="btn btn-primary">
        <span class="glyphicon glyphicon-envelope" aria-hidden="true"></span>
        &nbsp;Recevoir par mail
    </a>

    <h2><?php echo count($transactions_list) ?> fichiers transmis non acquittés</h2>

    <div class="alert alert-info">
        Liste des transactions restées à l'état transmis (donc non-acquittées par Hélios) avant ce matin à minuit.
    </div>

    <table class="data-table table table-striped">
        <tr>
            <th scope="col">Nom du fichier</th>
            <th scope="col">Date de récupération</th>
            <th scope="col">Helios-Site destination</th>
        </tr>
        <?php
        foreach ($transactions_list as $transaction) :
            ?>
            <tr>
                <td><a href="/modules/helios/helios_transac_show.php?id=<?php echo $transaction['id'] ?>"><?php echo $transaction['filename'] ?></a>
                    <br/>
                    Nomfic : <?php echo $transaction['xml_nomfic'] ?>
                </td>
                <td><?php echo $transaction['submission_date'] ?></td>
                <td><?php echo $transaction['helios_ftp_dest'] ?></td>
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