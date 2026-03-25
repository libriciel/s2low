<?php

use App\Factory\ConnexionS2lowDBFactory;
use App\Factory\ConnexionSelfDBFactory;
use App\Factory\NewS3ClientFactory;
use App\Factory\OldS3ClientFactory;
use App\Migration\ActesSource;
use App\Migration\MailSource;
use App\Migration\PesAcquitSource;
use App\Migration\PesSource;
use App\Repository\ActesRepository;
use App\Repository\MailSecRepository;
use App\Repository\PesAcquitRepository;
use App\Repository\PesRepository;
use App\Service\DownloadTransaction;
use App\Service\TransactionImportFromS2low;
use App\Service\UploadTransaction;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

// Initialize Environment
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Parse CLI Args
$shortopts = "s:"; // -s step (import, download, upload, daemon)
$longopts  = [
    "step:",
];
$options = getopt($shortopts, $longopts);

$step = $options['step'] ?? $options['s'] ?? 'daemon';

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

// -------------------------------------------------------------------------
// INITIALIZATION OF PIPELINE SERVICES
// -------------------------------------------------------------------------

// 1. Import Handlers
$actesRepository = new ActesRepository($S2lowDBConnexion);
$pesRepository = new PesRepository($S2lowDBConnexion);
$pesAcquitRepository = new PesAcquitRepository($S2lowDBConnexion);
$mailRepository = new MailSecRepository($S2lowDBConnexion);

$savers = [
    new TransactionImportFromS2low(new ActesSource($actesRepository), $selfDBConnexion),
    new TransactionImportFromS2low(new PesSource($pesRepository), $selfDBConnexion),
    new TransactionImportFromS2low(new PesAcquitSource($pesAcquitRepository), $selfDBConnexion),
    new TransactionImportFromS2low(new MailSource($mailRepository), $selfDBConnexion)
];

// 2. Download Handler
$downloader = new DownloadTransaction($selfDBConnexion, $oldS3);

// 3. Upload Handler
$uploader = new UploadTransaction($selfDBConnexion, $newS3);

// -------------------------------------------------------------------------
// EXECUTION ROUTING
// -------------------------------------------------------------------------

function runImport(array $savers) {
    echo "--- Starting IMPORT Stage ---" . PHP_EOL;
    foreach ($savers as $saver) {
        $saver->run();
    }
    echo "--- Finished IMPORT Stage ---" . PHP_EOL;
}

function runDownload(DownloadTransaction $downloader) {
    echo "--- Starting DOWNLOAD Stage (Batch of 10) ---" . PHP_EOL;
    $downloader->run(10);
}

function runUpload(UploadTransaction $uploader) {
    echo "--- Starting UPLOAD Stage (Batch of 10) ---" . PHP_EOL;
    $uploader->run(10);
}


function runDaemon(\App\DatabaseAccess\SelfDB $db, UploadTransaction $uploader, DownloadTransaction $downloader, array $savers) {
    echo "Starting Migration Daemon (Single File Workflow)... Press Ctrl+C to stop." . PHP_EOL;
    while (true) {
        if (processPendingUpload($db, $uploader)) {
            continue;
        }

        if (processNextDownload($db, $downloader)) {
            continue;
        }

        // If nothing left to sync, look for new transactions (import batch) or just sleep if all done.
        runImport($savers);
        sleep(2);
    }
}

function processPendingUpload(\App\DatabaseAccess\SelfDB $db, UploadTransaction $uploader): bool {
    // MUST UPLOAD FIRST: If a local file already exists (from an interrupted run), upload it.
    $pendingUploads = $db->getTransactionsByStatus(\App\Enum\Status::DOWNLOADED, 1);
    if (!empty($pendingUploads)) {
        $uploader->processUpload($pendingUploads[0]);
        return true; 
    }
    return false;
}

function processNextDownload(\App\DatabaseAccess\SelfDB $db, DownloadTransaction $downloader): bool {
    // FETCH NEXT: We only reach here if 0 files are waiting to be uploaded.
    $pendingDownloads = $db->getTransactionsByStatus(\App\Enum\Status::HANDLE, 1);
    if (empty($pendingDownloads)) {
        $pendingDownloads = $db->getTransactionsByStatus(\App\Enum\Status::ASK, 1);
    }

    if (!empty($pendingDownloads)) {
        $downloader->processDownload($pendingDownloads[0]);
        return true; 
    }
    return false;
}


switch ($step) {
    case 'import':
        runImport($savers);
        break;
    case 'download':
        runDownload($downloader);
        break;
    case 'upload':
        runUpload($uploader);
        break;
    case 'daemon':
        runDaemon($selfDBConnexion, $uploader, $downloader, $savers);
        break;
    default:
        echo "Invalid step. Choose: import, download, upload, or daemon." . PHP_EOL;
        exit(1);
}
