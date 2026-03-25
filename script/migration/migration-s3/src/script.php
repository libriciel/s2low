<?php

use App\Enum\Type;
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
use App\Service\BucketResolver;
use App\Service\DownloadTransaction;
use App\Service\GlacierRestoreChecker;
use App\Service\TransactionImportFromS2low;
use App\Service\UploadTransaction;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

// Initialize Environment
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Parse CLI Args
$shortopts = "s:m:M:t:r"; // -s step, -m min-date, -M max-date, -t type, -r retry-errors
$longopts  = [
    "step:",
    "min-date:",
    "max-date:",
    "type:",
    "retry-errors"
];
$options = getopt($shortopts, $longopts);

$step = $options['step'] ?? $options['s'] ?? 'daemon';
$minDate = $options['min-date'] ?? $options['m'] ?? null;
$maxDate = $options['max-date'] ?? $options['M'] ?? null;
$typeFilter = $options['type'] ?? $options['t'] ?? null;
$retryErrors = isset($options['retry-errors']) || isset($options['r']);

// Calculate inclusive limit for SQL comparison
$maxDateLimit = null;
if ($maxDate) {
    try {
        $dt = new DateTime($maxDate);
        $dt->modify('+1 day');
        $maxDateLimit = $dt->format('Y-m-d');
    } catch (Exception $e) {
        echo "Erreur format date: '$maxDate'. Utilisez YYYY-MM-DD." . PHP_EOL;
        exit(1);
    }
}

// -------------------------------------------------------------------------
// TYPE FILTER : --type=acte,pes_aller,pes_acquit,mail
// -------------------------------------------------------------------------
$typeAliases = [
    'acte'       => Type::ACTE->value,
    'actes'      => Type::ACTE->value,
    'pes_aller'  => Type::PES_ALLER->value,
    'pes'        => Type::PES_ALLER->value,
    'pes_acquit' => Type::PES_ACQUIT->value,
    'acquit'     => Type::PES_ACQUIT->value,
    'mail'       => Type::MAIL->value,
];

$allowedTypes = null; // null = tous les types
if ($typeFilter) {
    $allowedTypes = [];
    foreach (explode(',', $typeFilter) as $alias) {
        $alias = strtolower(trim($alias));
        if (isset($typeAliases[$alias])) {
            $allowedTypes[] = $typeAliases[$alias];
        } else {
            echo "Type inconnu: '$alias'. Types valides: " . implode(', ', array_keys($typeAliases)) . PHP_EOL;
            exit(1);
        }
    }
    $allowedTypes = array_unique($allowedTypes);
    echo "Filtre de types actif: " . implode(', ', $allowedTypes) . PHP_EOL;
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

// -------------------------------------------------------------------------
// INITIALIZATION OF PIPELINE SERVICES
// -------------------------------------------------------------------------

// 1. Import Handlers
$actesRepository = new ActesRepository($S2lowDBConnexion);
$pesRepository = new PesRepository($S2lowDBConnexion);
$pesAcquitRepository = new PesAcquitRepository($S2lowDBConnexion);
$mailRepository = new MailSecRepository($S2lowDBConnexion);

$allSavers = [
    Type::ACTE->value       => new TransactionImportFromS2low(new ActesSource($actesRepository), $selfDBConnexion),
    Type::PES_ALLER->value  => new TransactionImportFromS2low(new PesSource($pesRepository), $selfDBConnexion),
    Type::PES_ACQUIT->value => new TransactionImportFromS2low(new PesAcquitSource($pesAcquitRepository), $selfDBConnexion),
    Type::MAIL->value       => new TransactionImportFromS2low(new MailSource($mailRepository), $selfDBConnexion)
];

// Filtrer les savers selon --type
if ($allowedTypes) {
    $savers = array_values(array_intersect_key($allSavers, array_flip($allowedTypes)));
} else {
    $savers = array_values($allSavers);
}

// 2. Resolve Handler
$resolver = new BucketResolver($selfDBConnexion, $oldS3);

// 3. Download Handler
$downloader = new DownloadTransaction($selfDBConnexion, $oldS3);

// 4. Restore Handler
$restoreChecker = new GlacierRestoreChecker($selfDBConnexion, $oldS3);

// 5. Upload Handler
$uploader = new UploadTransaction($selfDBConnexion, $newS3);

// -------------------------------------------------------------------------
// RETRY ERRORS ?
// -------------------------------------------------------------------------
if ($retryErrors) {
    echo "--- Retrying transactions in ERROR status ---" . PHP_EOL;
    // We pass the type filter to resetErrors if provided
    $count = $selfDBConnexion->resetErrors($allowedTypes);
    echo "Done: $count transactions reset to HANDLE." . PHP_EOL;
}

// -------------------------------------------------------------------------
// EXECUTION ROUTING
// -------------------------------------------------------------------------

function runImport(array $savers, ?string $minDate = null, ?string $maxDateLimit = null) {
    foreach ($savers as $saver) {
        $saver->run(null, $minDate, $maxDateLimit);
    }
}

function runResolve(BucketResolver $resolver) {
    $resolver->run(10);
}

function runDownload(DownloadTransaction $downloader) {
    $downloader->run(10);
}

function runUpload(UploadTransaction $uploader) {
    $uploader->run(10);
}

function runCheckRestore(GlacierRestoreChecker $restoreChecker, ?array $allowedTypes = null) {
    $restoreChecker->run(50, $allowedTypes);
}


function runDaemon(\App\DatabaseAccess\SelfDB $db, UploadTransaction $uploader, DownloadTransaction $downloader, BucketResolver $resolver, array $savers, ?string $minDate = null, ?array $allowedTypes = null, ?string $maxDate = null, GlacierRestoreChecker $restoreChecker) {
    echo "Starting Migration Daemon... Press Ctrl+C to stop." . PHP_EOL;
    while (true) {
        $didWork = false;

        // PRIORITE 1 : Upload (1 fichier à la fois pour protéger le disque)
        $pendingUploads = $db->getTransactionsByStatus(\App\Enum\Status::DOWNLOADED, 1, $allowedTypes);
        if (!empty($pendingUploads)) {
            $uploader->processUpload($pendingUploads[0]);
            continue;
        }

        // PRIORITE 2 : Download (1 fichier à la fois pour protéger le disque)
        $pendingDownloads = $db->getTransactionsByStatus(\App\Enum\Status::BUCKET_FOUND, 1, $allowedTypes);
        if (!empty($pendingDownloads)) {
            $downloader->processDownload($pendingDownloads[0]);
            continue;
        }

        // PRIORITE 2bis : Legacy ASK -> RESTORING (batch de 100)
        $pendingAsk = $db->getTransactionsByStatus(\App\Enum\Status::ASK, 100, $allowedTypes);
        if (!empty($pendingAsk)) {
            foreach ($pendingAsk as $item) {
                $db->updateStatus($item, \App\Enum\Status::RESTORING);
            }
            $didWork = true;
        }

        // PRIORITE 3 : Resolve en batch (50 d'un coup)
        $pendingResolves = $db->getTransactionsByStatus(\App\Enum\Status::HANDLE, 50, $allowedTypes);
        if (!empty($pendingResolves)) {
            foreach ($pendingResolves as $item) {
                $resolver->processBucketResolve($item);
            }
            $didWork = true;
        }

        // PRIORITE 4 : Glacier check (batch 50)
        $restoreChecker->run(50, $allowedTypes);

        // PRIORITE 5 : Import si rien d'autre à faire
        if (!$didWork) {
            runImport($savers, $minDate, $maxDate);
            sleep(2);
        }
    }
}


switch ($step) {
    case 'import':
        runImport($savers, $minDate, $maxDateLimit);
        break;
    case 'resolve':
        runResolve($resolver);
        break;
    case 'download':
        runDownload($downloader);
        break;
    case 'upload':
        runUpload($uploader);
        break;
    case 'check-restore':
        runCheckRestore($restoreChecker, $allowedTypes);
        break;
    case 'daemon':
        runDaemon($selfDBConnexion, $uploader, $downloader, $resolver, $savers, $minDate, $allowedTypes, $maxDateLimit, $restoreChecker);
        break;
    default:
        echo "Invalid step. Choose: import, resolve, download, upload, or daemon." . PHP_EOL;
        exit(1);
}
