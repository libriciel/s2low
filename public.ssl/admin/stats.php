<?php

require_once( __DIR__ . "/../../init/init-www-helios.php");

if ($userInfo['role'] != 'SADM'){
	$_SESSION["error"] = "Super admin only !";
	header("Location: " . WEBSITE);
	exit();
}

//nombre de transaction/mois

$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);

$nb_transactions_list = $actesTransactionsSQL->getNbTransactionByMonth();

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


		<h2>Actes</h2>
		<table  class="data-table table table-striped ">
			<tr>
				<th>Mois</th>
				<th>Nombre de transactions</th>
			</tr>
			<?php foreach($nb_transactions_list as $nb_transaction_info): ?>
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
