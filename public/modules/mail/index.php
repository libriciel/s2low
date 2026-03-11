<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayoutFactory;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Mail\MailLayout;
use S2lowLegacy\Lib\ObjectInstancierFactory;
use S2lowLegacy\Mail\MailMessageEmis;
use S2lowLegacy\Mail\MailPeer;
use S2lowLegacy\Mail\MailTransaction;

require_once('../../../init/init.php');
LegacyObjectsManager::setLegacyObjectInstancier();
/** @var \S2lowLegacy\Class\HTMLLayoutFactory $htmlLayoutFactory */
$htmlLayoutFactory = LegacyObjectsManager::getObject(HTMLLayoutFactory::class);

$mail_emis_id = Helpers::getVarFromGet('mail_emis_id');
$password = Helpers::getVarFromPost('mdp');


$mailEmis = new MailMessageEmis($mail_emis_id);
$mailEmis->init();
if (! $mailEmis) {
    $_SESSION['last_error'] = "Le message que vous avez demandé n'existe pas.";
    header('Location: error.php');
    exit;
}

$mail_id = $mailEmis->getMailTransactionId();

$mailTransaction = new MailTransaction($mail_id);
if (! $mailTransaction->init()) {
    $_SESSION['last_error'] = "Le message que vous avez demandé n'existe pas.";
    header('Location: error.php');
    exit;
}

if (! $mailTransaction->isPasswordOK($password)) {
    if ($password) {
        $_SESSION['last_error'] = 'Mot de passe incorrect';
    }
    $redirectUrl = 'Location: password.php?mail_emis_id=' . urlencode($mail_emis_id);
    header($redirectUrl);
    exit;
}

$mailEmis->acquitter();
$mailTransaction->updateStatus();

$mailTo = $mailTransaction->getEmailByType(MailMessageEmis::TYPE_MAIL_TO);
$mailCC = $mailTransaction->getEmailByType(MailMessageEmis::TYPE_MAIL_CC);

$fndownload = $mailTransaction->getFNDownload();
$mailIncludeFileArray = MailPeer::GetIncludeFiles($mail_id);

$doc = $htmlLayoutFactory->createLayout('xhtml_mail.tpl.php');
$doc->setTitle(WEBSITE_TITLE);

$objectInstancier = ObjectInstancierFactory::getObjetInstancier();
$localMailSecResolver  = $objectInstancier->get('app.localFileResolver.mailsec');
$cloudStoreMailSec  = $objectInstancier->get('app.store.file.mailsec');

if ($fndownload) {
    try {
        $cloudStoreMailSec->downloadFileFromCloud($mailTransaction->getId());
        $mailzip_filepath = $localMailSecResolver->getFullPath($mailTransaction->getId());

        $filesize = filesize($mailzip_filepath);
    } catch (Exception $e) {
        $filesize = 'Fichier non disponible';
    }
}

$doc->DisplayHead();


?>
<script src="/javascript/mailshow.js" type="text/javascript"></script>
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div id="list_area">
                <div class="lecture_mail">
                    <h2>Message reçu</h2>
                    <div class="col_gauche">Envoyé à :</div>
                    <div class="col_droite">
                        <?php echo get_hecho($mailTo);?>
                    </div>

                    <div class="col_gauche_info">Envoyé le :</div>
                    <div class="col_droite_info">
                        <?php echo $mailTransaction->getDateEvnoi(); ?>
                    </div>

                    <?php if ($mailCC) : ?>
                    <div class="col_gauche_info">CC :</div>
                    <div class="col_droite_info">
                        <?php echo get_hecho($mailCC); ?>
                    </div>
                    <?php endif;?>

                    <div class="col_gauche">Objet :</div>
                    <div class="col_droite">
                        <span class="objet"><?php echo get_hecho($mailTransaction->getObjet()); ?></span>
                    </div>

                    <div class="col_gauche">Message :</div>
                    <div class="col_droite">
                        <?php echo nl2br(strip_tags($mailTransaction->getMessage())); ?>
                    </div>
                    <br class="clear" />

                    <?php  if ($mailIncludeFileArray) : ?>      
                    <h2 id="pj_desc">Pièces jointes</h2>
    
                    <div class="col_gauche_pj">&nbsp;</div>
                    <div class="col_droite_pj">
                        <table aria-describedby="pj_desc">
                            <thead>
                                <tr>
                                    <th id="file" class="align_left">Nom du fichier</th>
                                    <th id="size">Taille </th>
                                    <th id="type">Type </th>
                                    <th id="download">Télécharger</th>
                                </tr>
                            </thead>    
                            <tbody>
                        <?php foreach ($mailIncludeFileArray as $mailIncludeFile) : ?>
                                <tr>
                                    <td class="align_left" ><?php echo $mailIncludeFile->getFileName(); ?></td>
                                    <td><?php echo $mailIncludeFile->getFileSize(); ?></td>
                                    <td class="force_maj"><?php echo $mailIncludeFile->getFileType(); ?></td>
                                    <td><a href="download.php?filename=<?php echo urlencode($mailIncludeFile->getFileName()); ?>&root=<?php echo $fndownload; ?>">Télécharger</a></td>
                                </tr>
                        <?php endforeach; ?>
                                <tr>
                                    <td class="align_left">&lt;Télécharger tous les fichiers&gt; </td>
                                    <td><?php echo $filesize; ?></td>
                                    <td class="force_maj">zip</td>
                                    <td><a href="download.php?filename=mail.zip&root=<?php echo $fndownload; ?>">Télécharger</a></td>
                                </tr>

                            </tbody>
                        </table>
                </div>
                <br class="clear" />
                    <?php   else : ?>
                <h2>Ce mail ne comporte pas de pièces jointes</h2>  
                    <?php endif; ?> 
            </div><!-- list-area-->
        </div><!-- col-md-12 -->
    </div><!-- row -->
</div><!-- container -->
<?php
$doc->DisplayFoot();
