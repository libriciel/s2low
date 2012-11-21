<?php 

require_once( __DIR__ . "/../../../init/init-www-mailsec.php");

if (! $droit->isAuthorityAdmin($userInfo)){
	exit;
}
$recuperateur = new Recuperateur($_GET);
$menuHTML = new MenuHTML();

$id = $recuperateur->getInt('id');


$mailAnnuaireSQL = new MailAnnuaireSQL($sqlQuery);
$info = $mailAnnuaireSQL->getInfo($id);

if (! $info){
	$id = "";
	$info = array("email" => "","description" => "");
}

$doc = new HTMLLayout();
$doc->setTitle(($id?"Edition":"Ajout")." d'un contact de l'annuaire - Mail sécurisé - S²low");

$doc->addBody($menuHTML->getMenu($userInfo,$modulesInfo));
ob_start();
?>
<div id="content">
<h1> Carnet d'adresse </h1>  	
  	
<h2> Edition d'un contact</h2>  	

<div class="data_table">
	<form action="index.php?command=annuaire" method="post">
		<input type='hidden' name='id' value='<?php echo $id ?>' />
		<table style="width: 100%;">
			<tbody>
 				<tr>
  					<td class="td-register">Nom&nbsp;:</td>
  					<td class="td-input">
  						<input size="40" maxlength="128" name="description" type="text" value="<?php hecho($info['description']) ?>"/>
  					</td>
 				</tr>
	 			<tr>
  					<td class="td-register">Adresse email&nbsp;:</td>
  					<td class="td-input"><input size="40" maxlength="128" name="email" type="text" value="<?php hecho($info['mail_address']) ?>" /></td>
 				</tr>
			</tbody>
		</table>
		<input class="submit_button" value="<?php echo $id?"Modifier":"Ajouter" ?>" type="submit" />
	</form>
</div>

</div>
<?php 			
$html = ob_get_contents();
ob_end_clean();

$doc->addBody($html);
$doc->buildFooter();
$doc->display();