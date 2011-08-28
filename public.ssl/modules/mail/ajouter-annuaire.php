<?php 
require_once("include/init.php");
if (! $me->isAuthorityAdmin()){
  		exit;
  	}
require_once ("lib/MailLayout.class.php");
$doc = new MailLayout();
$doc->disableError(); 
$doc->setTitle("Gestion du carnet d'adresse");
$doc->buildMenu($me);

$doc->DisplayHead();
?>
<div id="content">
<h1> Carnet d'adresse </h1>  	
  	
<h2> Ajout d'email</h2>  	


<div class="data_table">
	<form action="index.php?command=annuaire" method="post">
		<table style="width: 100%;">
			<tbody>
 				<tr>
  					<td class="td-register">Nom&nbsp;:</td>
  					<td class="td-input">
  						<input size="40" maxlength="128" name="description" type="text" />
  					</td>
 				</tr>
	 			<tr>
  					<td class="td-register">Adresse email&nbsp;:</td>
  					<td class="td-input"><input size="40" maxlength="128" name="email" type="text" /></td>
 				</tr>
			</tbody>
		</table>
		<input class="submit_button" value="Ajouter une nouvelle adresse" type="submit" />
	</form>
</div>

</div>

<?php 
$doc->DisplayFoot();	