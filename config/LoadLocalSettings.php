<?php

if (file_exists(__DIR__ . "/LocalSettings.php")) {
    //Il est possible d'écraser les valeurs par défaut en
    //créant un fichier LocalSettings.php

    require_once(__DIR__ . "/LocalSettings.php");
}

foreach (glob("/data/config/*.php") as $file_name) {
    include_once($file_name);
}
