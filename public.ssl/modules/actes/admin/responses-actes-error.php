<?php

use S2lowLegacy\Class\actes\ActesResponsesError;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\MenuHTML;
use S2lowLegacy\Class\PagerHTML;

require_once(__DIR__ . "/../../../../init/init-www-actes.php");

if ($userInfo['role'] != 'SADM') {
    $_SESSION["error"] = "Super admin only !";
    header("Location: " . WEBSITE);
    exit();
}


$actesResponsesError = $objectInstancier->get(ActesResponsesError::class);
$nb_responses_error = $actesResponsesError->getNbError();

$errorFileIterator = $actesResponsesError->getFilesystemIterator();


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
        <h1>Actes - Dématérialisation du contrôle de légalité</h1>
        <p id="back-user-btn"><a href="/admin/index.php" class="btn btn-default" title="">Retour console d'administration</a></p>
        <h2><?php echo $nb_responses_error ?> mails reçus en erreur</h2>

    <div class="alert alert-info">
        Liste des fichiers trouvés reçu par mail mais dont l'analyse a échoué.
    </div>

        <table class="data-table table table-striped">
            <tr>
                <th>Nom du fichier</th>
                <th>Date de récupération</th>
                <th>Supprimer</th>
                <th>Analyser à nouveau</th>
            </tr>
            <?php
                /** @var DirectoryIterator $errorFile */
            foreach ($errorFileIterator as $errorFile) :
                ?>
                <tr>
                    <td><a href="/modules/actes/admin/download-response.php?file=<?php echo urlencode($errorFile->getFilename()) ?>"><?php echo $errorFile->getFilename() ?></a></td>
                    <td><?php echo date("Y-m-d H:i:s", $errorFile->getCTime()) ?></td>
                    <td class="text-center">
                        <a class="btn btn-danger"
                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce fichier ?');"
                           href="/modules/actes/admin/delete-response.php?file=<?php echo urlencode($errorFile->getFilename()) ?>"
                        >
                            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                        </a>
                    </td>
                    <td class="text-center">
                        <a class="btn btn-info"
                           href="/modules/actes/admin/analyse-response.php?file=<?php echo urlencode($errorFile->getFilename()) ?>"
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