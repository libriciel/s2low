<?php 
include("init.php");

$authority_id = null;

$authorities = $me->getAllPossibleAuthority();

if (count($authorities)> 1){
	$authority_id = Helpers::getVarFromGet('authority_id');
} else {
	$authority_id = array_keys($authorities);
	$authority_id= $authority_id[0];
}

$groupes = array();

if ($authority_id){	
	$groupes = $serviceUser->getServiceUser($authority_id);
}

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : gestion des services");

$doc->buildMenu($me);

ob_start();?>
<div id="content">
<h1>Gestion des services</h1>

<?php if(count($authorities) > 1): ?>
<h2>Choix de la collectivité</h2>
	<?php if ( ! $authority_id): ?>
	<ul>
	<?php foreach($authorities as $id=>$name): ?>
		<li><a href='admin_services.php?authority_id=<?php echo $id?>'><?php echo $name?></a></li>
	<?php endforeach;?>
	</ul>
	<?php else : ?>
		<a href='admin_services.php'>Voir une autre collectivité</a>
	<?php endif;?>
<?php endif;?>

<?php if ($authority_id) : ?>
<h2>Liste des services <?php if(count($authorities) > 1): ?>
(<?php echo $authorities[$authority_id]?>)
<?php endif;?></h2>

<form action='add-service-user.php' method='post'>
<input type='hidden' name='authority_id' value='<?php echo $authority_id ?>'/>
<label for='name'> Ajouter un service </label> 
<input type='text' name='name' id='name'/>
<input type='submit' value='ajouter'/>
</form>

<ul>
<?php foreach($groupes as $info) : ?>
<li><a href='gestion-service-content.php?id=<?php echo $info['id']?>'><?php echo $info['name']?></a> </li>
<?php endforeach;?>
</ul>
<?php endif;?>
<?php 
$html = ob_get_contents();
ob_end_clean();


$doc->addBody($html);

$doc->buildFooter();

$doc->display();