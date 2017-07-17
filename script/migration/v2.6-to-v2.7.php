<?php


require_once( __DIR__."/../../init/init.php");



$sql = "UPDATE actes_status SET name='Acquittement de document reçu' WHERE id=11";
$sqlQuery->query($sql);

echo "Mise à jour du libellé du status 11 - Aquittement de document reçu -> Acquittement de document reçu\n";