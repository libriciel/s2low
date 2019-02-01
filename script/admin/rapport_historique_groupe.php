<?php

require_once( __DIR__."/../../init/init.php");



function compteactes($siren,$sqlQuery){
	$sql="SELECT sum(file_size) as total,count(*) as nombre FROM actes_envelopes WHERE siren like ?";
	$actes=$sqlQuery->queryOne($sql,$siren);
	$message = '';
	$size=0;
	//var_dump($actes);exit;
        $size=$actes["total"];
	echo "Taille totale des enveloppes actes : ".$size." et nombre d'enveloppes ".$actes["nombre"]."\n";

	return $size;
}

function comptehelios($authority_id,$sqlQuery){
	$sql="select sum(file_size) as total ,count(*) as nombre FROM helios_transactions WHERE authority_id= ?";
	$helios=$sqlQuery->queryOne($sql,$authority_id);
	$message = '';
	$size=0;
	$size=$helios["total"];
	echo "Taille totale des flux PES_ALLER : $size et le nombre de PES ".$helios["nombre"]."\n";

	return $size;
}


$message = '';
$mail=EMAIL_ADMIN_TECHNIQUE;
// Param�tre attendu id coll
// Retourne la taille du dossier actes et la taille des flux PES

/*if (empty($argv[1])){
    echo "Usage : {$argv[0]} authority_id\n";
    exit;
}
$authority_id=$argv[1];
*/

//$list_gpe=array('14','49','53','10','67','1','60','63','12','55','66','54','70','71','72','74','75','78','80','83','84','87','64','90','57','92','94','99','65','4','91');
$list_gpe=array('1','99','3','54','84','55','83','105','14','72','104','89','65','10','91','60','67','94','64','53','2','98','74','66','4','61','106','50');
$total=0;
$units = 'BKMGTP';

foreach($list_gpe as $gpe){
	$sql="select id from authorities where authority_group_id=?";
	$listidcoll=$sqlQuery->query($sql,$gpe);
	//var_dump($listidcoll);exit;
	foreach($listidcoll as $id){
		$authority_id=$id['id'];
		echo "coll id : ".$authority_id."\n";
		//exit;

		//$sql="SELECT name FROM authorities WHERE id = ?";
		//$namecoll=$sqlQuery->queryOne($sql,$authority_id);


		$sql="SELECT siren FROM authorities WHERE id= ?";
		$siren=$sqlQuery->queryOne($sql,$authority_id);
		echo "$siren\n";
		/*
		$message.="---------------------------------\n".
		    "id : $authority_id \n".
		    "nom de la collectivite : $namecoll\n";
		*/
		// ACTES
		$total += compteactes($siren,$sqlQuery);

		// HELIOS
		$total += comptehelios($authority_id,$sqlQuery);
		echo $total." --> $gpe --> ".$id['id']."\n";
		$factor = (int)(log($total, 1000));
		$size = sprintf("%.2f", $total / pow(1000, $factor)) . @$units[$factor];
		echo $size."\n";
		//exit;
	}
}
echo $total."\n";
$factor = (int)(log($total, 1000));
$size = sprintf("%.2f", $total / pow(1000, $factor)) . @$units[$factor];
echo $size;
//mail($mail,"Rapport taille historique collectivite $namecoll",$message);
