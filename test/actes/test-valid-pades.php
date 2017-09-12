<?php

require_once __DIR__."/../../init/init.php";

$padesValid = $objectInstancier->get("PadesValid");

print_r($padesValid->validate(__DIR__."/Courrier_signe.pdf"));