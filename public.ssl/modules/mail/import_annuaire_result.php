<?php

use S2lowLegacy\Class\MailInit;
use S2lowLegacy\Mail\MailLayout;

list($module, $me, $myAuthority) = MailInit::getIdentificationParameters();
if (! $me->isAuthorityAdmin()) {
        exit;
}
if (isset($_SESSION['last_annuaire'])) {
    $annuaire = $_SESSION['last_annuaire'];

    $tabError = $annuaire->getTabError();
    $tabAlreadyExists = $annuaire->getTabAlreadyExist();
    $tabOK = $annuaire->getTabOK();

unset($_SESSION['last_annuaire']);
} else {
    $annuaire = null;
    $tabOK = [];
    $tabAlreadyExists = [];
    $tabError = [];
}

$doc = new MailLayout();
$doc->disableError();
$doc->setTitle("Gestion du carnet d'adresses");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$doc->DisplayHead();

function affiche20Premier($texte, $tab)
{
    if ($tab) : ?>
        <div>
            <h3><?php echo $texte ?>: <?php echo count($tab); ?></h3>
            
            <?php if (count($tab) > 20) : ?>
                <br/>Voici les 20 premiers :    <br/>   
            <?php endif;?>
            <ul>
            <li><?php echo $tab[0]?></li>
            <?php for ($i = 1; $i < min(20, count($tab)); $i++) :?>
                <li><?php echo $tab[$i] ?></li>
            <?php endfor;?>         
            <?php if (count($tab) > 20) : ?>
                <li>...</li>    
            <?php endif;?>
            </ul>
        </div>
    <?php endif;
}
?>

        <?php $doc->afficheErrors(); ?>
        <h1>Carnet d'adresses</h1>
        <h2>Actions</h2>
        <div id="actions_area"> 
            <a href="index.php?command=annuaire" class="btn btn-primary">Liste des emails</a>
    </div>
        <?php if (! empty($annuaire)) : ?>
    <h2>Résultat de l'import</h2>
            <?php affiche20Premier("Nombre de nouvelles adresses emails enregistrés", $tabOK) ?>
            <?php affiche20Premier("Nombre d'adresses emails déjà dans la base", $tabAlreadyExists) ?>
            <?php affiche20Premier("Nombre de lignes du fichier en erreur", $tabError) ?>
        <?php endif;?>

    <h2>Importer un fichier</h2>
    
    <div class="data_table">
            <form action="import_annuaire.php" method="post" enctype="multipart/form-data" >
                <input type="file" name="carnet" />
                <input type='submit' value="envoyer" class="btn btn-default"/>
            </form>
    </div>
<?php
$doc->closeContent(true);
$doc->closeContainer(true);

$doc->DisplayFoot();
