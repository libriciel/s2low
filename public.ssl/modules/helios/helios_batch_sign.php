<?php

use S2lowLegacy\Class\helios\HeliosSignature;
use S2lowLegacy\Class\helios\PesAllerRetriever;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\MenuHTML;
use S2lowLegacy\Controller\LibersignController;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowLegacy\Model\ModuleSQL;

/** @var Initialisation $initialisation */
/** @var ModuleSQL $moduleSQL */
/** @var HeliosTransactionsSQL $heliosTransactionSQL */
/** @var PesAllerRetriever $pesAllerRetriever */
/** @var LibersignController $libersignController */
/** @var string $html */

[
    $initialisation,
    $moduleSQL,
    $heliosTransactionSQL,
    $pesAllerRetriever,
    $libersignController
] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([
        Initialisation::class,
        ModuleSQL::class,
        HeliosTransactionsSQL::class,
        PesAllerRetriever::class,
        LibersignController::class,
        ]);
$html = '';
$initData = $initialisation->doInit();
$moduleData = $initialisation->initModule($initData, Initialisation::MODULENAMEHELIOS);

if (! $moduleSQL->hasDroit($moduleData->moduleInfo['id'], $initData->connexion->getId(), 'CS')) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(
        1,
        'Vous ne disposez pas du droit de signature.',
        \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/helios/index.php')
    );
}

$liste_id = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost('liste_id');

if (!$liste_id) {
    \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(
        1,
        'Vous devez sélectionner au moins une transaction à signer.',
        \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/helios/index.php')
    );
}



$transaction_list = [];

$heliosSignature = new HeliosSignature();

foreach ($liste_id as $transaction_id) {
    try {
        $transactionInfo = $heliosTransactionSQL->getInfo($transaction_id);
        if ($transactionInfo['authority_id'] != $initData->userInfo['authority_id']) {
            \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(
                1,
                "Vous n'avez pas le droit de signature sur la transaction n°{$transactionInfo['id']}",
                \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/helios/index.php')
            );
        }
        $pesaller_path = $pesAllerRetriever->getPath($transactionInfo['sha1']);
        $signature = $heliosSignature->getInfoForSignature($pesaller_path);
        $transactionInfo['bordereau_hash'] = $signature['bordereau_hash'];
        $transactionInfo['bordereau_id'] = $signature['bordereau_id'];
        $transactionInfo['isbordereau'] = $signature['isbordereau'];
        $transaction_list[] = $transactionInfo;
    } catch (Exception $e) {
        \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(
            1,
            "Impossible de signer la transaction $transaction_id : " .
                "le fichier PES contient un bordereau qui n'a pas d'identifiant",
            \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/helios/index.php')
        );
    }
}


$menuHTML = new MenuHTML();

$doc = new HTMLLayout();
$doc->setTitle('Tedetis : Signature de plusieurs fichier PES');
$doc->openContainer();
$doc->openSideBar();
$doc->addBody($menuHTML->getMenuContent($initData->userInfo, $moduleData->modulesInfo));
$doc->closeSideBar();
$doc->openContent();


$html .= "<h1>HELIOS - Signature de plusieurs PES</h1>\n";
$html .= "<p id=\"back-transaction-btn\"><a class=\"btn btn-default\" href=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/helios/index.php") . "\" class=\"bouton\">Retour liste transactions</a></p>\n";

$html .= "<h2>Liste des fichiers à signer</h2>\n";

$html .= "<div id=\"lot-area\">\n";
$html .= "<table class=\"data-table table table-striped\">";
$html .= "<caption>Liste des lots de transactions<caption>\n";
$html .= "<thead>\n";
$html .= "<tr>\n";
$html .= " <th id=\"numero_helios\" class=\"data\">Numéro du fichier</th>\n";
$html .= " <th id=\"fichier_helios\" class=\"data\">Fichier</th>\n";
$html .= "</tr>\n";
$html .= "</thead>\n";
$html .= "<tbody>\n";

$i = 0;

foreach ($transaction_list as $transactionInfo) {
    $html .= "<tr class=\"alternate" . ($i + 1) . "\">\n";
    $html .= " <td headers=\"numero_acte\"><a href=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/helios/helios_transac_show.php?id=' . $transactionInfo['id']) . "\" title=\"Visualiser le PES\">" . $transactionInfo['id'] . "</a></td>\n";
    $html .= " <td headers=\"fichier_helios\">";
    $html .= "<a href=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/helios/helios_download_file.php?id=' . $transactionInfo['id']) . "\" title=\"Télécharger le fichier\">" . $transactionInfo['filename'] . '</a>';
    $html .= "</td>\n";
    $html .= "</tr>\n";

    $i = 1 - $i;
}
$html .= "</tbody>\n";
$html .= "</table>\n";
$html .= "</div>\n";



$html .= '<h3>Signature des fichiers PES</h3>';



ob_start();
$libersignController->displayLibersignJS();

?><div class='action'>
$libersignController
    <script>
        $(window).on('load',function() {

            $(document).ready(function () {

                $("#box_result").hide();

                var siginfos = [];
                <?php foreach ($transaction_list as $i => $transactionInfo) : ?>
                siginfos.push({
                    hash: "<?php echo $transactionInfo['bordereau_hash']?>",
                    pesid: "<?php echo $transactionInfo['bordereau_id']?>",
                    pespolicyid: "urn:oid:1.2.250.1.131.1.5.18.21.1.7",
                    pespolicydesc: "Politique de signature Helios de la DGFiP",
                    pespolicyhash: "roF9+cfRHNPtVJolhdqfIqGMVuUXX8aR4rpiquf0u5E=",
                    pesspuri: "https://www.collectivites-locales.gouv.fr/files/files/finances_locales/dematerialisation/ps_helios_dgfip.pdf",
                    pescity: "<?php hecho($initData->authorityInfo['city'])?>",
                    pespostalcode: "<?php hecho($initData->authorityInfo['postal_code'])?>",
                    pescountryname: "France",
                    pesclaimedrole: "Ordonnateur",
                    pesencoding: "iso-8859-1",
                    format: "xades-env-1.2.2-sha256"
                });
                <?php endforeach;?>

                $(".libersign").libersign({
                    iconType: "glyphicon",
                    signatureInformations: siginfos
                }).on('libersign.sign', function (event, signatures) {
                    console.log(signatures);
                    <?php foreach ($transaction_list as $i => $transactionInfo) : ?>
                    $("#signature_<?php echo $i + 1 ?>").val(signatures[<?php echo $i?>]);
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

    <form action='<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink('modules/helios/helios_transac_sign.php');?>' id='form_sign' method='post'>
        <input type='hidden' name='nb_signature'  value='<?php echo count($transaction_list)?>'/>
        <input type='hidden' name='id' id='form_sign_id' value='<?php echo 999 ?>'/>

        <?php foreach ($transaction_list as $i => $transactionInfo) :?>
            <input type='hidden' name='id_<?php echo $i + 1 ?>' value='<?php echo $transactionInfo['id'] ?>'/>
            <input type='hidden' name='signature_id_<?php echo $i + 1 ?>' value='<?php echo $transactionInfo['bordereau_id'] ?>' />
            <input type='hidden' name='signature_<?php echo $i + 1 ?>' id='signature_<?php echo $i + 1?>' value=''/>
            <input type='hidden' name='is_bordereau_<?php echo $i + 1 ?>' id='is_bordereau_<?php echo $i + 1 ?>' value='<?php echo $transactionInfo['isbordereau'] ?>'/>

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
