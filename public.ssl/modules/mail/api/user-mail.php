<?php

require_once(__DIR__ . "/../include/init-module-mail.php");

$db = DatabasePool::getInstance();

$annuaire = new Annuaire($db, $me->get('authority_id'));
foreach ($annuaire->getAllMail() as $entry) {
    echo $entry['id'] . ":" . $entry['mail_address'] . ":" . $entry['description'];

    foreach ($entry['groupe'] as $groupe) {
        echo ":$groupe";
    }
    echo "\n";
}
