<?php

require_once(__DIR__ . "/../../init/init.php");

/** @var PesAllerRetriever $pesAllerRetriever */
$pesAllerRetriever = $objectInstancier->get("PesAllerRetriever");

echo $pesAllerRetriever->getPath("76de99c53565cfd9af1f78478f90293ff0895cd2")."\n";
