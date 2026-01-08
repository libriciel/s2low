<?php

use App\SQLite;

require_once __DIR__ . '/../vendor/autoload.php';

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

initialiseDb();

SQLite::addTransaction('helios', 123, 'ko', 's3');

//S3::getTransaction($bucket, $path);
//OpenStack::getTransaction($bucket, $path);
//
//S3::addActeEnveloppe($localPath, $cloudPath);
//S3::addPesAller($localPath, $cloudPath);
//S3::addPesAcquit($localPath, $cloudPath);
//S3::addPesRetour($localPath, $cloudPath);
//S3::addMailSec($localPath, $cloudPath);


$res = SQLite::getConnection()->query("select * from transactions")->fetchAll();

echo json_encode($res).PHP_EOL;
