<?php
if (empty($_GET['q'])){
	return; 
}
$debut = utf8_decode($_GET['q']);

require_once("include/init.php");

header("Content-type: text/plain; charset=ISO-8859-1");

$bd = DatabasePool::getInstance();

$annuaire = new Annuaire($bd,$me->get('authority_id'));

foreach ($annuaire->getListeMailAndGroupe($debut) as $item){
	echo $item."\n";
}
