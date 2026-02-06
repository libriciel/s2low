<?php

use App\SQLite;
use Dotenv\Dotenv;

function initialiseEnv(): array
{
    $res = [];
    $res['errors'] = [];

    try {
        $dotenv = Dotenv::createImmutable(__DIR__ . "/..");
        $dotenv->load();
    } catch (\Exception $e) {
        echo $e->getMessage();
    }

    $env['OLD_S3_ACCESS_KEY'] = $_ENV['OLD_S3_ACCESS_KEY'] ?? false;
    $env['OLD_S3_SECRET_KEY'] = $_ENV['OLD_S3_SECRET_KEY'] ?? false;

    foreach ($env as $key => $value) {
        if ($value === false) {
            $res['errors'][] = ['key' => $key, 'empty' => true];
        }
    }

    foreach ($res['errors'] as $error) {
        echo("la var d'environnement " . $error['key'] . " n\'est pas defini.\n");
    }

    if (!empty($res['errors'])) {
        die();
    }

    $res['env'] = $env;
    return $res;
}
