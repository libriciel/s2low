<?php 
require_once("include/init.php");

require_once ("lib/MailLayout.class.php");
$doc = new MailLayout();
$doc->disableError(); 
$doc->setTitle("Gestion du carnet d'adresse");
$doc->buildMenu($me);

$doc->DisplayHead();
?>
<div id="content">
<h1> Carnet d'adresse </h1>  	
  	
<h2> Ajout d'un groupe</h2>  	

<div class="data_table">
	<form action="ajouter-groupe-controler.php" method="post">
		<table style="width: 100%;">
			<tbody>
 				<tr>
  					<td class="td-register">Nom du groupe&nbsp;:</td>
  					<td class="td-input">
  						<input size="40" maxlength="128" name="name" type="text" />
  					</td>
 				</tr>
			</tbody>
		</table>
		<input class="submit_button" value="Ajouter un nouveau groupe" type="submit" />
	</form>
</div>

</div>

<?php 
$doc->DisplayFoot();	