<?php 

require_once('../../../config/config.php');
require_once(SITEROOT . '/class/include.class.php');
require_once (MAIL_SITEROOT."/lib/MailLayout.class.php");

$doc = new MailLayout('xhtml_mail.tpl.php');
$doc->setTitle(WEBSITE_TITLE);
$mail_emis_id=Helpers::getVarFromGet("mail_emis_id");


$doc->DisplayHead();
?>
<div class="col-md-9">
  <h1>Mail : Espace de Mail sécurisé</h1>

<p>Vous avez besoin d'un mot de passe pour voir le contenu du mail.</p>

<form name="mailpsw" action="index.php?mail_emis_id=<?php echo $mail_emis_id;?>" method="POST">

  <div>
  Mot de passe: <input name='mdp' /> <p></p>
          		<input type="submit" name='submit' onclick="checkDownloadPW();"/>
  </div>
</form>
<?php 
$doc->DisplayFoot();