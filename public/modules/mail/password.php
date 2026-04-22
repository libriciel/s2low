<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Mail\MailLayout;

require_once('../../../init/init.php');
\S2lowLegacy\Class\LegacyObjectsManager::setLegacyObjectInstancier();

$doc = new MailLayout('xhtml_mail.tpl.php');
$doc->setTitle(WEBSITE_TITLE);
$mail_emis_id = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromGet("mail_emis_id");

$error_message = $_SESSION['last_error'] ?? "";
unset($_SESSION["last_error"]);

$doc->DisplayHead();
?>
    <div class="container">

<div >
  <h2>Document protégé par un mot de passe</h2>


    <div class="alert alert-info">
        <p>Vous avez besoin d'un mot de passe pour voir le contenu du mail.</p>
    </div>

    <?php if ($error_message) : ?>
        <div class="alert alert-danger">
            <strong><?php echo $error_message?></strong>
        </div>
    <?php endif; ?>

    <div id="filtering-area" >

        <form name="mailpsw" action="index.php?mail_emis_id=<?php hecho($mail_emis_id);?>" method="POST" class="form-horizontal">

            <div class="form-group">
                <label for="mdp" class="col-md-4 control-label">Mot de passe</label>
                <div class="col-md-4">
                    <input type='password' name='mdp' id="mdp"/>
                </div>

            </div>

            <div class="form-group">
                <button type="submit" class="col-md-offset-3 col-md-3 btn btn-default" onclick="checkDownloadPW();" >Accéder au contenu</button>
            </div>


        </form>
    </div>
</div>



<?php
$doc->DisplayFoot();
