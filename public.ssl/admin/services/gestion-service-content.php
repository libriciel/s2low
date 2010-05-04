<?php
include("init.php");
$id =  Helpers::getVarFromGet('id');

if (empty($id)){
	header("Location: admin_group_user.php");
}

//FIXME vérifier les droits !!!

$groupe = $serviceUser->getInfo($id);
$users = $serviceUser->getListUser($id);

$all_groupes = $serviceUser->getPossibleParent($groupe['authority_id'],$id);

$serviceEnfant = $serviceUser->getAllEnfant($id);

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : gestion des groupes utilisateur");

$doc->buildMenu($me);

ob_start();?>

<div id="content">
<h1>Gestion du service <?php echo $groupe['name'];?></h1>

<a href='admin_services.php?authority_id=<?php echo $groupe['authority_id']?>'>« Revenir à l'affichage des service</a>

<h2>Liste des utilisateurs de <?php echo $groupe['name']?></h2>
<div>

<?php if ($users) : ?>
	<form action='enlever-utilisateur.php' method='post'>
	<input type='hidden' name='id_service' value='<?php echo $id?>'>
	<ul>
	<?php foreach($users as $u) : ?>
		<li>
			<input type='checkbox' name='id_user[]' value='<?php echo $u['id_user']?>'>
			<a href='../users/admin_user_edit.php?id=<?php echo $u['id_user']?>'><?php echo $u['givenname'] ."&nbsp;".$u['name']?></a>
			
		</li>
	<?php endforeach;?>
	</ul>
	Pour la sélection : <input type='submit' value='enlever du service'/>
	</form>
<br/>
<?php else : ?>
Aucun utilisateur n'est dans le groupe <em><?php echo $groupe['name']?></em>.
<?php endif;?>
<p>Pour ajouter un utilisateur dans un groupe, allez sur la page de <a href='<?php echo WEBSITE_SSL?>/admin/users/admin_users.php'> gestion des utilisateurs</a>.
</div>

<h2>Groupe parent</h2>
<div>
<?php if ($groupe['parent_id']) : ?>
	Le groupe parent de <?php echo $groupe['name']?> est <a href='gestion-service-content.php?id=<?php echo $groupe['parent_id']?>'><?php echo $groupe['parent_name']?></a>
<?php else :?>
	<?php echo $groupe['name']?> n'a pas de groupe parent.
<?php endif;?>
</div>
<br/><br/>
<form action='add-parent.php' method='post'>
	<input type='hidden' name='id' value='<?php echo $id?>'>
	Mettre dans le groupe parent : <select name='service_id'>
		<option value='0'>(aucun)</option>
		<?php foreach($all_groupes as $grp) : ?>
			<option value='<?php echo $grp['id'] ?>' <?php if ($grp['id'] == $groupe['parent_id']) echo "selected='selected'"?>>
			<?php echo $grp['name']?></option>
		<?php endforeach;?>
	</select>
	<input type='submit' value='Valider'/>
</form>


<?php if ($serviceEnfant) : ?>
<h2>Groupe enfant</h2>
<?php foreach($serviceEnfant as $enfant): ?>
<a href='gestion-service-content.php?id=<?php echo $enfant['id']?>'><?php echo $enfant['name']?></a>&nbsp;
<?php endforeach;?>
<?php endif;?>


<h2>Suppression</h2>
<p>
<?php if (! $users && ! $serviceEnfant) : ?>

<form action="supprimer-service.php" method="post"  onsubmit="return confirm('Voulez-vous vraiment supprimer ce service ?')">
	<input type="hidden" name="id" value="<?php echo $id ?>" />
	<input type="submit" value="Supprimer ce service" class="bouton-danger" />
</form>
<?php else : ?>
Pour supprimer le service, il faut que celui-ci ne contienne plus d'utilisateur et ne soit pas parent d'un autre service.
<?php endif;?>
</p>
<?php 
$html = ob_get_contents();
ob_end_clean();


$doc->addBody($html);

$doc->buildFooter();

$doc->display();