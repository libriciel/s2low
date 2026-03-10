<?php

//UtilisÃ© dans le cadre de PHPStorm qui ne permet pas de lancer des scripts aprÃ¨s le dÃ©marrage du Docker
//et qui ne lance pas l'entrypoint
//Uniquement utilisÃ© pour PHPUnit et Codeception donc

//Et bien sur, ca interagi mal avec gitlab-ci...

if (! file_exists("/etc/s2low/DockerSettings.php")) {
    echo "DockerSettings n'existe pas : crÃ©ation Ã  partir des variables d'environnement\n";
    $script = __DIR__ . "/generate-config.sh";

    shell_exec("/bin/bash $script > /tmp/DockerSettings.php");

    require_once "/tmp/DockerSettings.php";

    // Toujours sur phpstorm, y a un bug avec Ã§a...
    //https://www.quora.com/How-do-I-fix-Class-PHPUnit_Util_Configuration-not-found-error-in-PHPUNIT-2
    if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
        define('PHPUNIT_COMPOSER_INSTALL', __DIR__ . '/../vendor/autoload.php');
    }

    exec("/bin/bash " . __DIR__ . "/create-directory-structure.sh");
    return;
}
