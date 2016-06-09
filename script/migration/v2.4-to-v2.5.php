<?php


require_once( __DIR__."/../../init/init.php");

echo "Migration S2low 2.4 vers 2.5\n\n";

//1. Passage au nouveau système de notification forcé pour tout le monde.
echo "Passage au nouveau système de notification: ";
$sql = "UPDATE authorities SET new_notification=true";
$sqlQuery->query($sql);
echo "[DONE]\n";


echo "\nMigration terminée\n";


