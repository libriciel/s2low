<?php

/*
 * Les transactions helios versé et accepté par le SAE n'ont pas été supprimé sur Pastell
 *
 *
 */

require_once ( __DIR__."/../../init/init.php");


$heliosTransactionsSQL = new HeliosTransactionsSQL($sqlQuery);


$all = $heliosTransactionsSQL->getArchiveFromStatusWithSAE(10);

$count = count($all);

foreach($all as $i => $transaction_info){
	echo "$i/$count\n";

	echo "Transaction {$transaction_info['id']} - Pastell {$transaction_info['sae_transfer_identifier']}\n";

	$pastell = new Pastell($transaction_info['pastell_url'],
		$transaction_info['pastell_id_e'],
		$transaction_info['pastell_login'],
		$transaction_info['pastell_password']);


	$info = $pastell->getInfo($transaction_info['sae_transfer_identifier']);

	if (! $info){
		echo "Ok !\n";
		continue;
	}
	echo "[Pastell]: ";
	print_r($info['info']);

	echo "oops : la transaction existe toujours sur Pastell !\n";


	$data = $pastell->delete($transaction_info['sae_transfer_identifier']);

	if (! $data){
		echo "impossible de supprimer !";
		exit;
	}
	echo "[Pastell]\n";
	print_r($data);



}
