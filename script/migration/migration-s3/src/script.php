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
$key = '211927207/691BAV2007/SLO-EACT--211927207--20071130-2.tar.gz';
$localFile = '/var/www/html/data/actes/SLO-EACT--211927207--20071130-2.tar.gz';

$object = $oldS3->getFile($bucket, $key, $localFile);

//$object = $oldS3->test();

echo (PHP_EOL.json_encode($object).PHP_EOL);
