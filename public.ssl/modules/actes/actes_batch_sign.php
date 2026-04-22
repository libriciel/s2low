<?php

use S2lowLegacy\Class\actes\ActesIncludedFileSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\MenuHTML;
use S2lowLegacy\Controller\LibersignController;
use S2lowLegacy\Model\ModuleSQL;

/** @var Initialisation $initialisation */
/** @var ModuleSQL $moduleSQL */
/** @var ActesTransactionsSQL $actesTransactionSQL */
/** @var ActesIncludedFileSQL $actesIncludedFileSQL */
/** @var LibersignController $libersignController */
/** @var string $html */

[
        $initialisation,
    $moduleSQL,
    $actesTransactionSQL,
    $actesIncludedFileSQL,
    $libersignController
] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [
                    Initialisation::class,
                ModuleSQL::class,
                ActesTransactionsSQL::class,
                ActesIncludedFileSQL::class,
                LibersignController::class,
            ]
    );
$html = '';
$initData = $initialisation->doInit();
$moduleData = $initialisation->initModule($initData, Initialisation::MODULENAMEACTES, Initialisation::DROITSACTES);

if (! $moduleSQL->hasDroit($moduleData->moduleInfo['id'], $initData->connexion->getId(), 'CS')) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, 'Vous ne disposez pas du droit de signature.', \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/actes/index.php'));
}

$liste_id = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost('liste_id');

if (!$liste_id) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Vous devez sélectionner au moins une transaction à signer.", \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/actes/index.php'));
}

$transaction_list = array();

foreach ($liste_id as $transaction_id) {
     $transactionInfo = $actesTransactionSQL->getInfo($transaction_id);
    if ($transactionInfo['authority_id'] != $initData->userInfo['authority_id']) {
        \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Vous n'avez pas le droit de signature sur la transaciton n°{$transactionInfo['id']}", \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/index.php"));
    }

     $tab_included_files = $actesIncludedFileSQL->getSendFile($transaction_id);
     $transactionInfo['file'] = $tab_included_files[0];
     $transaction_list[] = $transactionInfo;
}

$menuHTML = new MenuHTML();

$doc = new HTMLLayout();
$doc->setTitle("Tedetis : Signature de plusieurs Actes");
$doc->openContainer();
$doc->openSideBar();
$doc->addBody($menuHTML->getMenuContent($initData->userInfo, $moduleData->modulesInfo));
$doc->closeSideBar();
$doc->openContent();


$html .= "<h1>ACTES - Signature de plusieurs Actes</h1>\n";
$html .= "<p id=\"back-transaction-btn\"><a class=\"btn btn-default\" href=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/\" class=\"bouton\">Retour liste transactions</a></p>\n");

$html .= "<h2>Liste des fichiers à signer</h2>\n";

$html .= "<div id=\"lot-area\">\n";
$html .= "<table class=\"data-table table table-striped\" summary=\"Ce tableau présente respectivement un lien vers le détail, une description, la date, le nombre de fichiers non traités et un lien vers les actions disponibles de chaque lot\">";
$html .= "<caption>Liste des lots de transactions<caption>\n";
$html .= "<thead>\n";
$html .= "<tr>\n";
$html .= " <th id=\"numero_acte\" class=\"data\">Numéro de l'acte</th>\n";
$html .= " <th id=\"numero_interne_acte\" class=\"data\">Numéro interne de l'acte</th>\n";
$html .= " <th id=\"objet_actes\" class=\"data\">Objet</th>\n";
$html .= " <th id=\"fichier_actes\" class=\"data\">Fichier</th>\n";
$html .= "</tr>\n";
$html .= "</thead>\n";
$html .= "<tbody>\n";

$i = 0;

foreach ($transaction_list as $transactionInfo) {
    $html .= "<tr class=\"alternate" . ($i + 1) . "\">\n";
    $html .= " <td headers=\"numero_acte\"><a href=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_transac_show.php?id=" . $transactionInfo['id'] . "\" title=\"Visualiser l'actes\">" . $transactionInfo['id'] . "</a></td>\n");
    $html .= " <td headers=\"numero_interne_acte\">" . get_hecho($transactionInfo['number']) . "</td>\n";
    $html .= " <td headers=\"objet_actes\">" . $transactionInfo['subject'] . "</td>\n";
    $html .= " <td headers=\"fichier_actes\">";
    $html .= "<a href=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_download_file.php?file=" . $transactionInfo['file']['id'] . "\" title=\"Télécharger le fichier\">" . $transactionInfo['file']['posted_filename'] . "</a>");
    $html .= "</td>\n";
    $html .= "</tr>\n";

    $i = 1 - $i;
}
$html .= "</tbody>\n";
$html .= "</table>\n";
$html .= "</div>\n";



$html .= "<h3>Signature de l'acte</h3>";

$id = 999;
ob_start();
$libersignController->displayLibersignJS();

?>

    <script>
        $(window).on('load',function() {
            $(document).ready(function () {

                $("#box_result").hide();

                var siginfos = [];

                <?php foreach ($transaction_list as $i => $transactionInfo) : ?>
                siginfos.push({
                    hash: "<?php echo $transactionInfo['file']['sha1'] ?>",
                    format: "CMS"
                });
                <?php endforeach;?>

                $(".libersign").libersign({
                    iconType: "glyphicon",
                    signatureInformations: siginfos
                }).on('libersign.sign', function (event, signatures) {
                    <?php foreach ($transaction_list as $i => $transactionInfo) : ?>
                    $("#signature_<?php echo $i + 1?>").val(signatures[<?php echo $i ?>]);
                    <?php endforeach;?>
                    $("#form_sign").submit();
                });

            });
        });
    </script>

    <div id='box_signature' class='box' style="width:920px" >
        <h2>Signature</h2>
        <div class="libersign"></div>
    </div>

    <form action='<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("modules/actes/actes_transac_sign.php")?>' id='form_sign' method='post'>
        <input type='hidden' name='nb_signature'  value='<?php echo count($transaction_list)?>'/>
        <?php foreach ($transaction_list as $i => $transactionInfo) : ?>
            <input type='hidden' name='signature_id_<?php echo $i + 1?>' value='<?php echo $transactionInfo['file']['id']?>' />
            <input type='hidden' name='signature_<?php echo $i + 1?>' id='signature_<?php echo $i + 1 ?>' value=''/>
        <?php endforeach;?>
    </form>

    <?php
        $html .= ob_get_contents();
        ob_end_clean();

    $doc->addBody($html);

    $doc->closeContent();
    $doc->closeContainer();

    $doc->buildFooter();
    $doc->display();
