<?php
require_once( __DIR__ . "/../../../init/init-www-dia.php");

$doc = new HTMLLayout();
$doc->setTitle("Importer une DIA (test) - S²low");
$doc->addCSS("/custom/styles/date-picker.css");
$doc->addJavascript("/javascript/date-picker.js");
$doc->addJavascript("/javascript/tedetis.js");
$menuHTML = new MenuHTML();

$doc->addBody($menuHTML->getMenu($userInfo,$modulesInfo));

ob_start();
?>
<div class="col-md-9">
	<h1>DIA - Déclaration d'intention d'aliéner</h1>
	<h2>Importer d'un fichier (test)</h2>
	<form method="POST" enctype="multipart/form-data" action="<?php echo WEBSITE_SSL ?>/modules/dia/dia_reception.php" >
	<table  style='text-align:right'>
		<tr>
			<td>Fichier DIA (zip tel qu'envoyer par PEC/Presto) : </td>
			<td><input type="file" name="dia"/></td>
		</tr>
	</table>
	<input class="submit_button" type="submit" value=" Importer un fichier" >
	</form>
</div>
<?php 			
$html = ob_get_contents();
ob_end_clean();

$doc->addBody($html);
$doc->buildFooter();
$doc->display();