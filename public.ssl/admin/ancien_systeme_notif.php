<?php

require_once( __DIR__ . "/../../init/init-www-helios.php");

if ($userInfo['role'] != 'SADM'){
	$_SESSION["error"] = "Super admin only !";
	header("Location: " . WEBSITE);
	exit();
}


$sql = " SELECT authorities.name, authorities.id, authority_groups.name as group_name FROM authorities " .
		" JOIN authority_groups ON authorities.authority_group_id = authority_groups.id ".
		" JOIN modules_authorities ON authorities.id=modules_authorities.authority_id ".
		" JOIN modules ON modules_authorities.module_id=modules.id AND modules.name=?".
		" WHERE new_notification=? ORDER BY authority_groups.name, authorities.name";

$authorities_list = $sqlQuery->query($sql,'actes',0);


$sql_user = " SELECT DISTINCT email from users " .
			" JOIN users_perms ON users_perms.user_id=users.id ".
			" JOIN modules ON users_perms.module_id=modules.id AND modules.name='actes' AND (users_perms.perm='RO' OR users_perms.perm='RW')".
			" WHERE users.authority_id=?";


$menuHTML = new MenuHTML();
$pagerHTML  = new PagerHTML();

$doc = new HTMLLayout();

$doc->setTitle("Console d'administration");

$doc->openContainer();

$doc->addBody($menuHTML->getMenu($userInfo,$modulesInfo));

$doc->closeSideBar();


$doc->openContent();

ob_start();
?>
	<div id="content">
		<h1>Ancien système de notification</h1>

	<div class="alert alert-info">
		Sur cette page, on ne présente que les collectivités abonnés au module actes qui utilise l'ancien système de notification.
	</div>

<table class="data-table table table-striped">
	<tr>
		<th>Collectivité</th>
		<th>Groupes</th>
		<th>Email</th>
	</tr>
	<?php foreach($authorities_list as $authority_info) :
		$user_list = $sqlQuery->query($sql_user,$authority_info['id']);

		?>
		<tr>
			<td><?php hecho($authority_info['name'])?></td>
			<td><?php hecho($authority_info['group_name'])?></td>
			<td>
				<ul>
				<?php foreach($user_list as $user_info):  ?>
					<li><?php echo $user_info['email'] ?></li>
				<?php endforeach; ?>
				</ul>
			</td>
		</tr>
	<?php endforeach; ?>

</table>

<?php
$html = ob_get_contents();
ob_end_clean();
$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();
$doc->buildFooter();
$doc->display();
