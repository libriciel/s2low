<?php 

require_once( __DIR__ . "/../../../init/init-www-actes.php");


$recuperateur = new Recuperateur($_GET);
$id = $recuperateur->getInt('id');

$authorityInfo = $authoritySQL->getInfo($id);

if (! $authorityInfo  || ! $droit->hasDroit($userInfo,$authorityInfo)){
	sortir("Accès refusé");
}

$menuHTML = new MenuHTML();

$doc = new HTMLLayout();
$doc->setTitle("Configuration de la connexion SAE - S²low");
$doc->addBody($menuHTML->getMenu($userInfo,$modulesInfo));


ob_start();
?>
<div id="content">
	<h1>ACTES - Dématérialisation du contrôle de légalité</h1>
	<h2>Modification des propriétés SAE de <?php echo $authorityInfo['name']?></h2>
	<a href='admin_authority_edit.php?id=<?php echo $id ?>'>« revenir au formulaire standard</a><br/>
	<form action='admin_authority_sae_controler.php' method='post'>
	<input type='hidden' name='id' value='<?php echo $id ?>' />
	<table>
		
		<?php foreach(AuthoritySQL::getSAEProperties() as $sae_name => $sae_label):
			?>
			<tr>
				<td class="td-register"><?php echo $sae_label ?>&nbsp;:</td>
				<td class="td-input">
					<input type="<?php echo AuthoritySQL::getSAEPropertiesType($sae_name)?>" size="30" name="<?php echo $sae_name ?>" value="<?php echo htmlspecialchars($authorityInfo[$sae_name]) ?>" />
				</td>
			</tr>
		<?php endforeach; ?>
		
	</table>
		<center>
			<input type="submit" class="submit_button" value="Modifier" />
			
		</center>
		
	</form>

<a href='admin_authority_sae_text_connexion.php?id=<?php echo $id ?>'>Tester la connexion</a>

</div>
<?php 
$html = ob_get_contents();
ob_end_clean();

$doc->addBody($html);
$doc->buildFooter();
$doc->display();

