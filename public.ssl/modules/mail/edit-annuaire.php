<?php

use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\mailsec\MailAnnuaireSQL;
use S2lowLegacy\Class\MenuHTML;
use S2lowLegacy\Lib\Recuperateur;

require_once(__DIR__ . "/../../../init/init-www-mailsec.php");

if (! $droit->isAuthorityAdmin($userInfo)) {
    exit;
}
$recuperateur = new Recuperateur($_GET);
$menuHTML = new MenuHTML();

$id = $recuperateur->getInt('id');


$mailAnnuaireSQL = new MailAnnuaireSQL($sqlQuery);
$info = $mailAnnuaireSQL->getInfo($id);

if (! $info) {
    $id = "";
    $info = array("email" => "","description" => "");
}

$doc = new HTMLLayout();
$doc->setTitle(($id ? "Edition" : "Ajout") . " d'un contact de l'annuaire - Mail sécurisé - S²low");

$doc->openContainer();
$doc->openSideBar();
$doc->addBody($menuHTML->getMenuContent($userInfo, $modulesInfo));
$doc->closeSideBar();
$doc->openContent();

ob_start();
?>

    <h1> Carnet d'adresses </h1>

    <h2> Edition d'un contact</h2>      

    <div class="data_table">
        <form class="form form-horizontal" action="index.php?command=annuaire" method="post">
            <input type='hidden' name='id' value='<?php echo $id ?>' />
            <div class="form-group">
                <label class="col-md-2 label-form">Nom : </label>
                <div class="col-md-4">
                    <input size="40" class="form-control" maxlength="128" name="description" type="text" value="<?php hecho($info['description']) ?>"/>
                </div>
            </div>
            <div class="form-group">
                <label class="col-md-2 label-form">Adresse email : </label>
                <div class="col-md-4">
                    <input class="form-control" size="40" maxlength="128" name="email" type="text" value="<?php hecho($info['mail_address']) ?>" />
                </div>
            </div>
            <div class="form-group">
                <input class="btn btn-primary" value="<?php echo $id ? "Modifier" : "Ajouter" ?>" type="submit" />
            </div>
        </form>
    </div>


<?php
$html = ob_get_contents();
ob_end_clean();

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();