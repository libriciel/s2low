<?php

require_once(__DIR__ . "/../init/init.php");


$authority_id = $sqlQuery->queryOne(
    "INSERT INTO authorities (id, status, name) VALUES(nextval('authorities_id_seq'), 1, 'Administrateurs') RETURNING id"
);

echo "Création de l'entité $authority_id\n";
