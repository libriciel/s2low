<?php

require_once( __DIR__ . "/../../init/init-www-helios.php");

if ($userInfo['role'] != 'SADM'){
	$_SESSION["error"] = "Super admin only !";
	header("Location: " . WEBSITE);
	exit();
}


$info = array(
	1=>"Posté",2=>"En attente de transmission",3=>"Transmis"
);

/** @var HeliosTransactionsSQL $heliosTransactionsSQL */
$heliosTransactionsSQL = $objectInstancier->{'HeliosTransactionsSQL'};

$result = array();
foreach($info as $status_id => $status_libelle){
	$result[$status_id] =  $heliosTransactionsSQL->getNbByStatus($status_id);
}

$today = date("Y-m-d");

$nb_transaction_transmise_hier = $heliosTransactionsSQL->getNbByStatusAndDate(3,$today);


$heliosResponsesError = new HeliosResponsesError();

$nb_responses_error = $heliosResponsesError->getNbError();


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
		<h1>Console d'administration</h1>
		

		<h2>Helios : Nombre de transactions en cours</h2>
		<table  class="data-table table table-striped ">
			<tr>
				<th>Type</th>
				<th>Nombre de transactions</th>
				<th>&nbsp;</th>
			</tr>
			<?php foreach($result as $status_id => $nb): ?>
				<tr>
					<td><?php echo $info[$status_id]?></td>
					<td><?php echo $nb?></td>
					<td>

						<a href="/modules/helios/index.php?status=<?php echo $status_id ?>" class="icon">
							Liste
						</a>
					</td>
				</tr>
			<?php endforeach ?>
			<tr class="<?php echo $nb_transaction_transmise_hier?"danger":"success" ?>">
				<td>Transmise avant le <?php echo $today ?> (00h00)</td>
				<td><span class="label label-<?php echo $nb_transaction_transmise_hier?"danger":"success" ?>"><?php echo $nb_transaction_transmise_hier ?></span></td>
				<td>
					<a href="/modules/helios/admin/transmis-non-acquitte.php" >
						Liste
					</a>
				</td>
			</tr>
			<tr class="<?php echo $nb_responses_error?"danger":"success" ?>">
				<td>Fichiers reçus depuis Helios en erreur</td>
				<td><span class="label label-<?php echo $nb_responses_error?"danger":"success" ?>"><?php echo $nb_responses_error ?></span></td>
				<td>
					<a href="/modules/helios/admin/responses-helios-error.php" >
						Liste
					</a>
				</td>
			</tr>



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
