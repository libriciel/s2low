<?php 


require_once('../../../config/config.php');
require_once(SITEROOT . '/class/include.class.php');
require_once (MAIL_SITEROOT."/lib/MailLayout.class.php");

$doc = new MailLayout('xhtml_mail.tpl.php');
$doc->setTitle(WEBSITE_TITLE);




$doc->DisplayHead();
?>

<div id="content">
  <h1>Mail - Système de mail sécurisé</h1>

<p>Problème d'affichage du mail.</p>
<p>Message d'erreur:</p>
<p><?php echo $_SESSION['last_error']?></p>
<?php 
$doc->DisplayFoot();