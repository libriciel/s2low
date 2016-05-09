<?php

require_once( __DIR__ . "/../../../init/init-www-helios.php");

if ($userInfo['role'] != 'SADM'){
	$_SESSION["error"] = "Super admin only !";
	header("Location: " . WEBSITE);
	exit();
}


$info = array(
	1=>"Posté",2=>"En attente de transmission",3=>"Transmis"
);

$result = array();
foreach($info as $status_id => $status_libelle){
	$sql = "SELECT count(*) FROM helios_transactions WHERE last_status_id=?";
	$result[$status_id] = $sqlQuery->queryOne($sql,$status_id);
}

$sql = "SELECT count(*) FROM helios_transactions WHERE last_status_id=3";




$menuHTML = new MenuHTML();
$pagerHTML  = new PagerHTML();

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : module helios statistique");

$doc->openContainer();

$doc->addBody($menuHTML->getMenu($userInfo,$modulesInfo));

$doc->closeSideBar();


$doc->openContent();

ob_start();
?>
<div id="content">
	<h1>Helios : status en cours DGFIP</h1>

	<h2>Liste des status</h2>
<table  class="data-table table table-striped ">
	<tr>
		<th>Status</th>
		<th>Nombre de transactions</th>
	</tr>
	<?php foreach($result as $status_id => $nb): ?>
		<tr>
			<td><?php echo $info[$status_id]?></td>
			<td><?php echo $nb?></td>
		</tr>
	<?php endforeach ?>


</table>
</div>

<?php
$html = ob_get_contents();
ob_end_clean();
$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();
$doc->buildFooter();
$doc->display();
