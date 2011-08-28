<?php 
require_once("include/init.php");
if (! $me->isAuthorityAdmin()){
  		exit;
  	}
if (isset($_SESSION['last_annuaire'])) {
	$annuaire = $_SESSION['last_annuaire'];
	
	$tabError = $annuaire->getTabError();
	$tabAlreadyExists = $annuaire->getTabAlreadyExist();
	$tabOK = $annuaire->getTabOK();
	
	unset($_SESSION['last_annuaire']);
}

require_once ("lib/MailLayout.class.php");
$doc = new MailLayout();
$doc->disableError(); 
$doc->setTitle("Gestion du carnet d'adresse");
$doc->buildMenu($me);

$doc->DisplayHead();

function affiche20Premier($texte,$tab){
	if ($tab) : ?>
		<div>
			<h3><?php echo $texte ?>: <?php echo count($tab); ?></h3>
			
			<?php if (count($tab) > 20) : ?>
				<br/>Voici les 20 premiers : 	<br/>	
			<?php endif;?>
			<ul>
			<li><?php echo $tab[0]?></li>
			<?php for ($i=1; $i<min(20,count($tab)); $i++):?>
				<li><?php echo $tab[$i] ?></li>
			<?php endfor;?>			
			<?php if (count($tab) > 20) : ?>
				<li>...</li>	
			<?php endif;?>
			</ul>
		</div>
	<?php endif;	
}
?>

<div id="content">
<?php $doc->afficheErrors(); ?>
 <h1>Carnet d'adresse</h1>
<?php if(! empty($annuaire)) : ?>
	<h2>Résultat de l'import</h2>
	<?php affiche20Premier("Nombre de nouvelle adresse email enregistré",$tabOK) ?>
 	<?php affiche20Premier("Nombre d'adresse email déjà dans la base",$tabAlreadyExists) ?>
	<?php affiche20Premier("Nombre de ligne du fichier en erreur",$tabError) ?>		
<?php endif;?>

	<h2>Importer un fichier</h2>
	
	<div class="data_table">
	
	
		<form action="import_annuaire.php" method="post" enctype="multipart/form-data" >
			<input type="file" name="carnet" />
			<input type='submit' value="envoyer"/>
		</form>
		 
	</div>
	<br/><br/><br/>
	 <div id="actions_area"> 
		<a href="index.php?command=annuaire" class="bouton">Liste des emails</a>
	</div>
</div>
<?php 
$doc->DisplayFoot();	