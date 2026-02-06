<?php


use App\Factory\ConnexionS2lowDBFactory;
use App\Factory\ConnexionSelfDBFactory;
use App\Factory\NewS3ClientFactory;
use App\Factory\OldS3ClientFactory;
use App\Migration\ActesSource;
use App\Migration\MailSource;
use App\Migration\PesAcquitSource;
use App\Migration\PesSource;
use App\MigrationOrchestrator;
use App\Repository\ActesRepository;
use App\Repository\MailSecRepository;
use App\Repository\PesAcquitRepository;
use App\Repository\PesRepository;
use App\Service\TransactionSaver;
use App\Service\UnfreezeFile;
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
    "force"
];
$options = getopt($shortopts, $longopts);

$type = $options['type'] ?? $options['t'] ?? null;
$isDryRun = isset($options['dry-run']) || isset($options['d']);

if (!$type && !array_key_exists('force', $options)) {
    echo "Usage: php script.php --type=<actes|helios|helios_acquit|mail> [--dry-run]" . PHP_EOL;
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
    $selfDBConnexion = ConnexionSelfDBFactory::getConnection(
        $_ENV['SELF_DB_HOST'],
        $_ENV['SELF_DB_DB'],
        $_ENV['SELF_DB_USER'],
        $_ENV['SELF_DB_PASSWORD'],
        $_ENV['SELF_DB_PORT']
    );
    echo 'SelfDB Connection OK' . PHP_EOL;

} catch (Exception $e) {
    echo 'SelfDB Connection KO: ' . $e->getMessage();
    die();
}

try {
    $S2lowDBConnexion = ConnexionS2lowDBFactory::getConnection($_ENV);
    echo 'S2lowDB Connection OK' . PHP_EOL;

} catch (Exception $e) {
    echo 'S2lowDB Connection KO: ' . $e->getMessage();
    die();
}

$orchestrator = new MigrationOrchestrator(
    $newS3,
    $oldS3,
    $selfDBConnexion,
    $S2lowDBConnexion,
    $isDryRun
);

$orchestrator->checkCloudConnections();

//HANDLE
    $actesRepository = new ActesRepository($S2lowDBConnexion);
    $pesRepository = new PesRepository($S2lowDBConnexion);
    $pesAcquitRepository = new PesAcquitRepository($S2lowDBConnexion);
    $mailRepository = new MailSecRepository($S2lowDBConnexion);



    $migrationActe = new ActesSource(
        $actesRepository,
    );
    $migrationPes = new PesSource(
        $pesRepository,
    );
    $migrationPesAcquit = new PesAcquitSource(
        $pesAcquitRepository,
    );
    $migrationMail = new MailSource(
        $mailRepository,
    );



    $acteSaver = new TransactionSaver(
        $migrationActe,
        $selfDBConnexion
    );
    $pesSaver = new TransactionSaver(
        $migrationPes,
        $selfDBConnexion
    );
    $pesAcquitSaver = new TransactionSaver(
        $migrationPesAcquit,
        $selfDBConnexion
    );
    $mailSaver = new TransactionSaver(
        $migrationMail,
        $selfDBConnexion
    );

    $acteSaver->run();
    $pesSaver->run();
    $pesAcquitSaver->run();
    $mailSaver->run();
//////////////
//// UNFREEZE / CHECK EXIST

$unfreezeActe = new UnfreezeFile();
$unfreezeActe->run();

///////////
//// DOWNLOAD / SET ERROR


///////////





// Run Selected Flow
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
        echo "Aucun type selectionné" . PHP_EOL;
        exit(1);
}
