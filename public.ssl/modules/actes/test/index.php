<?php 
require_once(dirname(__FILE__)."/../../../../init/init-www-actes.php");

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : outils de test");

$doc->buildMenu($me);

ob_start();
?>
<div id="content">
<h2>Outils de test</h2>
<h3>Génération</h3>
<a href='enveloppe_generate.php'>Générer une enveloppe de test</a>


<h3>Validation</h3>
Valider une enveloppe
<form action='archive_verify.php' method='post' enctype='multipart/form-data'>
	<input type='file' name='archive'/>
	<input type='submit' />
</form>

</div>
<?php 
$html = ob_get_contents();
ob_end_clean();



$doc->addBody($html);

$doc->buildFooter();

$doc->display();
