<?php
if (empty($_GET['q'])){
	return; 
}
$debut = $_GET['q'];

require_once("include/init.php");

$bd = DatabasePool::getInstance();

$annuaire = new Annuaire($bd,$me->get('authority_id'));

foreach ($annuaire->getListeMailAndGroupe($debut) as $item){
	echo $item."\n";
}
