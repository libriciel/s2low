<?php

use S2lowLegacy\Class\actes\ActesResponsesError;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\HTMLLayoutFactory;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\MenuHTML;
use S2lowLegacy\Class\PagerHTML;
use S2lowLegacy\Class\UserContext;

/** @var Initialisation $initialisation */
/** @var ActesResponsesError $actesResponsesError */
/** @var UserContext $userContext */
/** @var HTMLLayoutFactory $htmlLayoutFactory */
[$initialisation,$actesResponsesError, $userContext, $htmlLayoutFactory] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class,ActesResponsesError::class, UserContext::class, HTMLLayoutFactory::class]);

$moduleData = $initialisation->initModule($userContext, Initialisation::MODULENAMEACTES, Initialisation::DROITSACTES);

if ($userContext->userInfo['role'] != 'SADM') {
    $_SESSION['error'] = 'Super admin only !';
    header('Location: ' . WEBSITE);
    exit();
}

$nb_responses_error = $actesResponsesError->getNbError();

$errorFileIterator = $actesResponsesError->getFilesystemIterator();


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
        <h1>Actes - Dématérialisation du contrôle de légalité</h1>
        <p id="back-user-btn">
            <a href="/admin/index.php" class="btn btn-default" title="">Retour console d'administration</a>
        </p>
        <h2><?php echo $nb_responses_error ?> mails reçus en erreur</h2>

    <div id="list_desc" class="alert alert-info">
        Liste des fichiers trouvés reçu par mail mais dont l'analyse a échoué.
    </div>

        <table class="data-table table table-striped" aria-describedby="list_desc">
            <tr>
                <th scope="col">Nom du fichier</th>
                <th scope="col">Date de récupération</th>
                <th scope="col">Supprimer</th>
                <th scope="col">Analyser à nouveau</th>
            </tr>
            <?php
                /** @var DirectoryIterator $errorFile */
            foreach ($errorFileIterator as $errorFile) :
                ?>
                <tr>
                    <td>
                        <a href="/modules/actes/admin/download-response.php?file=<?php
                        echo urlencode($errorFile->getFilename()) ?>">
                            <?php echo $errorFile->getFilename() ?>
                        </a>
                    </td>
                    <td><?php echo date('Y-m-d H:i:s', $errorFile->getCTime()) ?></td>
                    <td class="text-center">
                        <a class="btn btn-danger"
                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce fichier ?');"
                           href="/modules/actes/admin/delete-response.php?file=<?php
                            echo urlencode($errorFile->getFilename())
                            ?>"
                        >
                            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                        </a>
                    </td>
                    <td class="text-center">
                        <a class="btn btn-info"
                           href="/modules/actes/admin/analyse-response.php?file=<?php
                            echo urlencode($errorFile->getFilename()) ?>"
                        >
                            <span class="glyphicon glyphicon-retweet" aria-hidden="true" ></span>
                        </a>
                    </td>
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