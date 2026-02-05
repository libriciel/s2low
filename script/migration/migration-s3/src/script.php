<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\MigrationOrchestrator;
use App\NewS3;
use App\SourceStorage;
use App\StateTrackerRepository;
use Dotenv\Dotenv;

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

// Initialize Components
$source = new SourceStorage($_ENV);
$newS3 = new NewS3(
    $_ENV['NEW_S3_ENDPOINT'],
    $_ENV['NEW_S3_REGION'],
    $_ENV['NEW_S3_ACCESS_KEY'],
    $_ENV['NEW_S3_SECRET_KEY']
);

$stateTracker = new StateTrackerRepository();

$orchestrator = new MigrationOrchestrator($source, $newS3, $stateTracker, $isDryRun);

if (array_key_exists('check', $options)) {
//    $orchestrator->checkGlobalConnection();
    var_dump(
        $source->test(
            'sl-adullact-actes-2019',
            '212105340/002DU03122019/SLO-EACT--212105340--20191210-2.tar.gz',
            'acte/SLO-EACT--212105340--20191210-2.tar.gz'
        )
    );
    exit(0);
}

//// Run Selected Flow
//switch ($type) {
//    case 'actes':
//        $orchestrator->runActes();
//        break;
//    case 'helios':
//        $orchestrator->runHelios();
//        break;
//    case 'helios_acquit':
//        $orchestrator->runHeliosAcquit();
//        break;
//    case 'mail':
//        $orchestrator->runMail();
//        break;
//    default:
//        echo "Unknown type: $type" . PHP_EOL;
//        exit(1);
//}
