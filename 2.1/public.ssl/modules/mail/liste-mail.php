<?php
if (empty($_GET['q'])){
	return; 
}


require_once("include/init.php");
$debut = utf8_decode($_GET['q']);

header("Content-type: text/plain; charset=ISO-8859-1");

$bd = DatabasePool::getInstance();

$annuaire = new Annuaire($bd,$me->get('authority_id'));

foreach ($annuaire->getListeMailAndGroupe($debut) as $item){
	echo $item."\n";
}
