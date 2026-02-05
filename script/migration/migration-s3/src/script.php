<?php


use App\Factory\ConnexionS2lowDBFactory;
use App\Factory\ConnexionSelfDBFactory;
use App\Factory\NewS3ClientFactory;
use App\Factory\OldS3ClientFactory;
use App\MigrationOrchestrator;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';


// Initialize Environment
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Parse CLI Args
$shortopts = "t:d"; // -t type, -d dry-run
$longopts  = [
    "type:",
    "dry-run",
    "check"
];
$options = getopt($shortopts, $longopts);

$type = $options['type'] ?? $options['t'] ?? null;
$isDryRun = isset($options['dry-run']) || isset($options['d']);

if (!$type && !array_key_exists('check', $options)) {
    echo "Usage: php script.php --type=<actes|helios|helios_acquit|mail> [--dry-run] [--check]" . PHP_EOL;
    exit(1);
}

$newS3 = NewS3ClientFactory::getClient(
    $_ENV['NEW_S3_ENDPOINT'],
    $_ENV['NEW_S3_REGION'],
    $_ENV['NEW_S3_ACCESS_KEY'],
    $_ENV['NEW_S3_SECRET_KEY']
);

$oldS3 = OldS3ClientFactory::getClient(
    $_ENV['OLD_S3_ENDPOINT'],
    $_ENV['OLD_S3_REGION'],
    $_ENV['OLD_S3_ACCESS_KEY'],
    $_ENV['OLD_S3_SECRET_KEY']
);

try {
    $selfDBConnexion = ConnexionSelfDBFactory::getConnection('');
} catch (Exception $e) {
    echo 'erreur lors de la connexion a la SelfDB: ' . $e->getMessage();
    die();
}

try {
    $S2lowDBConnexion = ConnexionS2lowDBFactory::getConnection($_ENV);
} catch (Exception $e) {
    echo 'erreur lors de la connexion à la S2lowDB: ' . $e->getMessage();
    die();
}

$orchestrator = new MigrationOrchestrator(
    $newS3,
    $oldS3,
    $selfDBConnexion,
    $S2lowDBConnexion,
    $isDryRun
);

if (array_key_exists('check', $options)) {
    $orchestrator->checkCloudConnections();

    exit(0);
}

//    var_dump(
//        $source->test(
//            'sl-adullact-actes-2019',
//            '212105340/002DU03122019/SLO-EACT--212105340--20191210-2.tar.gz',
//            'acte/SLO-EACT--212105340--20191210-2.tar.gz'
//        )
//    );


//// Run Selected Flow
switch ($type) {
    case 'actes':
        $orchestrator->runActes();
        break;
    case 'helios':
        $orchestrator->runHelios();
        break;
    case 'helios_acquit':
        $orchestrator->runHeliosAcquit();
        break;
    case 'mail':
        $orchestrator->runMail();
        break;
    default:
        echo "Unknown type: $type" . PHP_EOL;
        exit(1);
}
