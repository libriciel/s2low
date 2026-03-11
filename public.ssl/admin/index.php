<?php

use S2lowLegacy\Class\actes\ActesResponsesError;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\helios\HeliosResponsesError;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\HTMLLayoutFactory;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\MenuHTML;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\PagerHTML;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Model\HeliosTransactionsSQL;

/** @var Initialisation $initialisation */
/** @var HeliosTransactionsSQL $heliosTransactionsSQL */
/** @var ActesTransactionsSQL $actesTransactionsSQL */
/** @var ActesResponsesError $actesResponsesError */
/** @var UserContext $userContext */
/** @var HTMLLayoutFactory $htmlLayoutFactory */

[
        $initialisation,
    $heliosTransactionsSQL,
    $actesTransactionsSQL,
    $actesResponsesError,
    $userContext,
    $htmlLayoutFactory
] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [Initialisation::class,HeliosTransactionsSQL::class,ActesTransactionsSQL::class,ActesResponsesError::class,
            UserContext::class, HTMLLayoutFactory::class]
    );

$moduleData = $initialisation->initModule($userContext, Initialisation::MODULENAMEHELIOS);

if ($userContext->userInfo['role'] != 'SADM') {
    $_SESSION['error'] = 'Super admin only !';
    header('Location: ' . WEBSITE);
    exit();
}


$helios_status = [
    1 => 'Posté',
    7 => 'En traitement',
    2 => 'En attente de transmission',
    3 => 'Transmis'
];

$helios_nb_transaction_by_status = [];
foreach ($helios_status as $status_id => $status_libelle) {
    $helios_nb_transaction_by_status[$status_id] =  $heliosTransactionsSQL->getNbByStatus($status_id);
}

$today = date('Y-m-d');

$nb_transaction_transmise_hier = $heliosTransactionsSQL->getNbByStatusAndDate(3, $today);

$heliosResponsesError = new HeliosResponsesError();
$helios_nb_responses_error = $heliosResponsesError->getNbError();


$actes_status = [
    1 => 'Posté',
    2 => 'En attente de transmission',
    3 => 'Transmis',
    7 => 'Document reçu',
];


$actes_nb_transaction_by_status = [];
foreach ($actes_status as $status_id => $status_libelle) {
    $actes_nb_transaction_by_status[$status_id] =  $actesTransactionsSQL->getNbByStatus($status_id);
}

$actes_nb_responses_error = $actesResponsesError->getNbError();

$nb_actes_transmis_4hours_before = $actesTransactionsSQL
    ->getNbByStatusAndDate(3, date('Y-m-d H:i:s', strtotime('-4 hours')));


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
        <h1>Console d'administration</h1>

        <h2>Message d'urgence</h2>

        <a href="/admin/message/index.php">Publier un message d'urgence</a>


        <h2 id="nb_transac_desc">Actes : Nombre de transactions en cours</h2>
        <table  class="data-table table table-striped " aria-describedby="nb_transac_desc">
            <tr>
                <th scope="col">Type</th>
                <th scope="col">Nombre de transactions</th>
                <th scope="col">&nbsp;</th>
            </tr>
            <?php foreach ($actes_nb_transaction_by_status as $status_id => $nb) : ?>
                <tr>
                    <td><?php echo $actes_status[$status_id]?></td>
                    <td><?php echo $nb?></td>
                    <td>

                        <a href="/modules/actes/index.php?status=<?php echo $status_id ?>" class="icon">
                            Liste
                        </a>
                    </td>
                </tr>
            <?php endforeach ?>
            <tr class="<?php echo $nb_actes_transmis_4hours_before ? 'danger' : 'success' ?>">
                <td>Actes transmis depuis plus de 4 heures</td>
                <td>
                    <span class="label label-<?php echo $nb_actes_transmis_4hours_before ? 'danger' : 'success' ?>">
                        <?php echo $nb_actes_transmis_4hours_before ?>
                    </span>
                </td>
                <td>
                    <?php $maxSubmissionDate = (new DateTime())->modify('-4 hours')->format('Y-m-dTh:m:s')?>
                    <a href="/modules/actes/index.php?status=3&max_submission_date=<?php echo $maxSubmissionDate?>"
                       class="icon">
                        Liste
                    </a>
                </td>
            </tr>
            <tr class="<?php echo $actes_nb_responses_error ? 'danger' : 'success' ?>">
                <td>Emails reçus depuis Actes en erreur</td>
                <td>
                    <span class="label label-<?php echo $actes_nb_responses_error ? 'danger' : 'success' ?>">
                        <?php echo $actes_nb_responses_error ?>
                    </span>
                </td>
                <td>
                    <a href="/modules/actes/admin/responses-actes-error.php" >
                        Liste
                    </a>
                </td>
            </tr>
        </table>




        <h2 id="nb_transac_desc">Helios : Nombre de transactions en cours</h2>
        <table  class="data-table table table-striped " aria-describedby="nb_transac_desc">
            <tr>
                <th scope="col">Type</th>
                <th scope="col">Nombre de transactions</th>
                <th scope="col">&nbsp;</th>
            </tr>
            <?php foreach ($helios_nb_transaction_by_status as $status_id => $nb) : ?>
                <tr>
                    <td><?php echo $helios_status[$status_id]?></td>
                    <td><?php echo $nb?></td>
                    <td>

                        <a href="/modules/helios/index.php?status=<?php echo $status_id ?>" class="icon">
                            Liste
                        </a>
                    </td>
                </tr>
            <?php endforeach ?>
            <tr class="<?php echo $nb_transaction_transmise_hier ? 'danger' : 'success' ?>">
                <td>Transmise avant le <?php echo $today ?> (00h00)</td>
                <td>
                    <span class="label label-<?php echo $nb_transaction_transmise_hier ? 'danger' : 'success' ?>">
                        <?php echo $nb_transaction_transmise_hier ?>
                    </span>
                </td>
                <td>
                    <a href="/modules/helios/admin/transmis-non-acquitte.php" >
                        Liste
                    </a>
                </td>
            </tr>
            <tr class="<?php echo $helios_nb_responses_error ? 'danger' : 'success' ?>">
                <td>Fichiers reçus depuis Helios en erreur</td>
                <td>
                    <span class="label label-<?php echo $helios_nb_responses_error ? 'danger' : 'success' ?>">
                        <?php echo $helios_nb_responses_error ?>
                    </span>
                </td>
                <td>
                    <a href="/modules/helios/admin/responses-helios-error.php" >
                        Liste
                    </a>
                </td>
            </tr>



        </table>
    </div>

<h2>Autres</h2>
<div class="alert alert-warning">
    Attention, page non optimisée qui ralentit le logiciel : <a href="stats.php">Statistiques</a>
</div>

<a href='/admin/stats-sae.php' class='btn  btn-primary'>Statistiques envoi SAE</a>


<?php
$html = ob_get_contents();
ob_end_clean();
$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();
$doc->buildFooter();
$doc->display();
