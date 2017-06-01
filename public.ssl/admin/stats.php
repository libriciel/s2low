<?php

require_once( __DIR__ . "/../../init/init-www-helios.php");

if ($userInfo['role'] != 'SADM'){
	$_SESSION["error"] = "Super admin only !";
	header("Location: " . WEBSITE);
	exit();
}

//nombre de transaction/mois

$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);
$nb_transactions_actes_list = $actesTransactionsSQL->getNbTransactionByMonth();

$heliosTransactionsSQL = new HeliosTransactionsSQL($sqlQuery);
$nb_transactions_helios_list = $heliosTransactionsSQL->getNbTransactionByMonth();



$fancyDate = new FancyDate();


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
		<h1>Statistiques (super admin)</h1>

		<div class="alert alert-warning">
			Attention, cette page n'est pas optimisée et ralentit l'ensemble de la plateforme. Merci d'utiliser avec la plus grande
			parcimonie pour les besoins du service.
		</div>

		<h2>Actes</h2>
		<table  class="data-table table table-striped ">
			<tr>
				<th>Mois</th>
				<th>Nombre de transactions</th>
			</tr>
			<?php foreach($nb_transactions_actes_list as $nb_transaction_info): ?>
				<tr>
					<td><?php echo $fancyDate->getMois($nb_transaction_info['month'])?></td>
					<td><?php echo $nb_transaction_info['nb']?></td>
				</tr>
			<?php endforeach ?>
		</table>

		<h2>Hélios</h2>
		<table  class="data-table table table-striped ">
			<tr>
				<th>Mois</th>
				<th>Nombre de transactions</th>
			</tr>
			<?php foreach($nb_transactions_helios_list as $nb_transaction_info): ?>
				<tr>
					<td><?php echo $fancyDate->getMois($nb_transaction_info['month'])?></td>
					<td><?php echo $nb_transaction_info['nb']?></td>
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
