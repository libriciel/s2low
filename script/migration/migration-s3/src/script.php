<?php

use App\OldS3;
use App\SQLite;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/init_functions.php';


// ------------------------ //

initialiseDb();
initialiseEnv();

$oldS3 = new OldS3();

$bucket = 'sladullact-actes2007';
$key = '';

$object = $oldS3->getFile($bucket, $key);

echo (PHP_EOL.$object['success']);
echo (PHP_EOL.$object['info']);
