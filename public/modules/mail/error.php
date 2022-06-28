<?php


require_once('../../../config/config.php');
require_once(SITEROOT . '/class/include.class.php');

$doc = new MailLayout('xhtml_mail.tpl.php');
$doc->setTitle(WEBSITE_TITLE);

$doc->DisplayHead();
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Mail - Système de mail sécurisé</h1>

            <div class="alert alert-danger">Problème d'affichage du mail.<br/>
                <strong><?php echo $_SESSION['last_error']?></strong>
            </div>
        </div>
    </div>
</div>
<?php
$doc->DisplayFoot();