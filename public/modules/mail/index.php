<?php 

require_once('../../../config/config.php');
require_once(SITEROOT . '/class/include.class.php');
require_once (MAIL_SITEROOT."/lib/MailLayout.class.php");
  
require_once (MAIL_SITEROOT."/om/mail_transaction.class.php");
require_once (MAIL_SITEROOT."/om/mail_message_emis.class.php");
require_once (MAIL_SITEROOT."/om/mail_included_file.class.php");
require_once (MAIL_SITEROOT."/om/MailPeer.class.php");

$mail_emis_id=Helpers::getVarFromGet("mail_emis_id");
$password=Helpers::getVarFromPost("mdp");


$mailEmis=new mail_message_emis($mail_emis_id);
$mailEmis->init();
if (! $mailEmis) {
	$_SESSION['last_error'] = "Le message que vous avez demandé n'existe pas.";
	header("Location: error.php");
	exit;
}

$mail_id=$mailEmis->getMailTransactionId();

$mailTransaction=new mail_transaction($mail_id);
if (! $mailTransaction->init()) {
	$_SESSION['last_error'] = "Le message que vous avez demandé n'existe pas.";
	header("Location: error.php");
	exit;
}

if (! $mailTransaction->isPasswordOK($password)){
	header("Location: password.php?mail_emis_id=".$mail_emis_id);
	exit;
}

$mailEmis->acquitter();
$mailTransaction->updateStatus();

$mailTo = $mailTransaction->getEmailByType(mail_message_emis::TYPE_MAIL_TO);
$mailCC = $mailTransaction->getEmailByType(mail_message_emis::TYPE_MAIL_CC);

$fndownload=$mailTransaction->getFNDownload();
$mailIncludeFileArray=MailPeer::GetIncludeFiles($mail_id);
	  	
$doc = new MailLayout('xhtml_mail.tpl.php');
$doc->setTitle(WEBSITE_TITLE);


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
                        <?php echo htmlentities($mailTo);?>
                    </div>

                    <div class="col_gauche_info">Envoyé le :</div>
                    <div class="col_droite_info">
                        <?php echo $mailTransaction->getDateEvnoi(); ?>
                    </div>

                    <?php if ($mailCC) : ?>

                    <div class="col_gauche_info">CC :</div>
                    <div class="col_droite_info">
                        <?php echo htmlentities($mailCC); ?>
                    </div>
                    <?php endif;?>

                    <div class="col_gauche">Objet :</div>
                    <div class="col_droite">
                        <span class="objet"><?php echo htmlentities($mailTransaction->getObjet()); ?></span>
                    </div>

                    <div class="col_gauche">Message :</div>
                    <div class="col_droite">
                        <?php echo nl2br(strip_tags($mailTransaction->getMessage())); ?>
                    </div>
                    <br class="clear" />

                    <?php  if ($mailIncludeFileArray) : ?>		
	
                    <h2>Pièces jointes</h2>
	
                    <div class="col_gauche_pj">&nbsp;</div>
                    <div class="col_droite_pj">
                        <table>
                            <thead>
                                <tr>
                                    <th id="file" class="align_left">Nom du fichier</th>
                                    <th id="size">Taille </th>
                                    <th id="type">Type </th>
                                    <th id="download">Télécharger</th>
                                </tr>
                            </thead>    
                            </body>    
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
                                    <td><?php echo filesize(MAIL_FILES_UPLOAD_ROOT.$fndownload.'/mail.zip'); ?></td>
                                    <td class="force_maj">zip</td>
                                    <td><a href="download.php?filename=mail.zip&root=<?php echo $fndownload; ?>">Télécharger</a></td>
                                </tr>

                            </tbody>
                        </table>
                </div>
                <br class="clear" />
	<?php  	else : ?>
                <h2>Ce mail ne comporte pas de pièces jointes</h2> 	
	<?php endif; ?>	
            </div><!-- list-area-->
        </div><!-- col-md-12 -->
    </div><!-- row -->
</div><!-- container -->
<?php 
$doc->DisplayFoot();