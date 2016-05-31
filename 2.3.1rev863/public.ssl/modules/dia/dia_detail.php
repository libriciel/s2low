<?php 

require_once( __DIR__ . "/../../../init/init-www-dia.php");

$recuperateur = new Recuperateur($_GET);
$id = $recuperateur->getInt('id',1);

$transactionDIA = new TransactionDIA($sqlQuery);

$dia_info = $transactionDIA->getInfo($id);
$workflow = $transactionDIA->getWorkflow($id);

$doc = new HTMLLayout();
$doc->setTitle("Détail d'une DIA - S²low");
$doc->addCSS("/custom/styles/date-picker.css");
$doc->addJavascript("/javascript/date-picker.js");
$doc->addJavascript("/javascript/tedetis.js");
$menuHTML = new MenuHTML();

$doc->addBody($menuHTML->getMenu($userInfo,$modulesInfo));

ob_start();
?>
<div class="col-md-9">
	<h1>DIA - Déclaration d'intention d'aliéner</h1>
	
	<center>
		<a href="<?php echo WEBSITE_SSL ?>/modules/dia/" class="bouton">
			Retour liste transactions
		</a>
	</center>
</div>
<div class="col-md-9">
	<h2>Détail</h2>
	<div class="data_table">
	<table class="data">
	<tr>
		<td class="td-register">Identifiant</td>
		<td class="td-input"><?php echo $dia_info['id'] ?></td>
	</tr>

	<tr>
		<td class="td-register">Fichier DIA</td>
		<td class="td-input"><b>
			<a href='<?php echo WEBSITE_SSL ?>/modules/dia/get_dia.php?id=<?php echo $dia_info['id']?>'><?php echo $dia_info['filename'] ?>
			</a>	</b>
		</td>
	</tr>
	<tr>
		<td class="td-register">Information PEC</td>
		<td class="td-input"><a href='<?php echo WEBSITE_SSL ?>/modules/dia/get_message_xml.php?id=<?php echo $dia_info['id']?>'><?php echo $dia_info['message_id'] ?></a></td>
	</tr>
	<?php if($dia_info['accuse_enregistrement']) :?>
	<tr>
		<td class="td-register">Accusé d'enregistrement</td>
		<td class="td-input"><b>
			<a href='<?php echo WEBSITE_SSL ?>/modules/dia/get_ae.php?id=<?php echo $dia_info['id']?>'><?php echo $dia_info['accuse_enregistrement'] ?>
			</a>	</b>
		</td>
	</tr>
	<?php endif;?>
	<?php if($dia_info['accuse_non_preemption']) :?>
	<tr>
		<td class="td-register">Accusé de non-préemption</td>
		<td class="td-input"><b>
			<a href='<?php echo WEBSITE_SSL ?>/modules/dia/get_anp.php?id=<?php echo $dia_info['id']?>'><?php echo $dia_info['accuse_non_preemption'] ?>
			</a>	</b>
		</td>
	</tr>
	<?php endif;?>
	</table>
	</div>
	<h2>Cycle de vie</h2>
		<div class="data_table">
  			<table class="workflow_list">
				<tr>
					<th>État</th>
					<th>Date</th>
					<th>Message</th>
				</tr>
				<?php  foreach ($workflow as $stage) : ?>
				 <tr>
					<td><?php echo TransactionDIA::getStatusName($stage["status_id"]) ?></td>
					<td><?php echo Helpers::getDateFromBDDDate($stage["date"], true) ?></td>
					<td><?php echo  nl2br($stage["message"]) ?></td>
				</tr>
				<?php endforeach;?>
				</table>
		</div>
	
	<?php if (in_array($dia_info['last_status_id'],array(2,4))) : ?>
	<h2>Envoyé l'accusé de non-préemption</h2>
	<form method="POST" enctype="multipart/form-data" action="<?php echo WEBSITE_SSL ?>/modules/dia/dia_reception_anp.php" >
	<input type="hidden" name="id" value='<?php echo $id ?>' />
	<table  style='text-align:right'>
		<tr>
			<td>Accusé de non préemption : </td>
			<td><input type="file" name="anp"/></td>
		</tr>
	</table>
	<input class="submit_button" type="submit" value=" Importer l'anp" >
	</form>
	<?php endif;?>
	<?php //if ($droit->isGroupOrSuperAdmin($userInfo)) : ?>
	<h2>Supprimer la DIA</h2>
	<form method="POST" action="<?php echo WEBSITE_SSL ?>/modules/dia/dia-delete.php" onsubmit="return confirm('Cette transaction sera héradiqué DEFINITIVEMENT de la base sans espoir de retour?')">
	<input type="hidden" name="id" value='<?php echo $id ?>' />

	<input  type="submit" value="Supprimer la DIA" class="bouton-danger" >
	</form>
	<?php //endif;?>
	
</div>
<?php 			
$html = ob_get_contents();
ob_end_clean();

$doc->addBody($html);
$doc->buildFooter();
$doc->display();
