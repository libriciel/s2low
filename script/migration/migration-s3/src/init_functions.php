<?php

use App\SQLite;
use Dotenv\Dotenv;

function initialiseDb()
{
    $connexion = SQLite::getConnection();
    $connexion->query(
        "CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    identifiant_s2low INTEGER,
    type_transaction TEXT NOT NULL,
    etat TEXT NOT NULL CHECK (etat IN ('ok', 'ko')),
    commentaire TEXT,
    source TEXT NOT NULL CHECK (source IN ('s3', 'openstack')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);"
    )->execute();
}

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

        if ($value === false)
        {
            $res['errors'][] = ['key' => $key, 'empty' => true];
        }
    }

    foreach ($res['errors'] as $error) {
        echo("la var d'environnement ".$error['key']." n\'est pas defini.\n");
    }

    if(!empty($res['errors']))
    {
        die();
    }

    $res['env'] = $env;
    return $res;
}
