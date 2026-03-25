<?php

/**
 * test-script.php - Outil de diagnostic S3
 *
 * Teste si une clé (key) existe dans les buckets S3, et affiche les métadonnées.
 *
 * Usage :
 *   php src/test-script.php <key> -t <type>
 *
 * Exemples :
 *   php src/test-script.php "212105340/002DU03122019/SLO-EACT--212105340--20191210-2.tar.gz" -t acte
 *   php src/test-script.php "123456789/abcdef1234567890" -t pes_aller
 *   php src/test-script.php "123456789/acquit.xml" -t pes_acquit
 *
 * Types valides : acte, actes, pes_aller, pes, pes_acquit, acquit, mail
 */

use App\Enum\Type;
use App\Factory\OldS3ClientFactory;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

// -------------------------------------------------------------------------
// PARSE CLI
// -------------------------------------------------------------------------

// Parse les options (-t type, -b bucket, -l list)
$shortopts = "t:b:l";
$longopts  = ["type:", "bucket:", "list"];
$restIndex = 0;
$options = getopt($shortopts, $longopts, $restIndex);
$typeAlias = $options['type'] ?? $options['t'] ?? null;
$bucketOption = $options['bucket'] ?? $options['b'] ?? null;
$listOnly = isset($options['l']) || isset($options['list']);

// Extraire la clé : c'est le premier argument positionnel (hors options)
$positionalArgs = array_slice($argv, $restIndex);
$key = $positionalArgs[0] ?? null;

// -------------------------------------------------------------------------
// INIT S3 (avant validation type si listOnly)
// -------------------------------------------------------------------------
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$oldS3 = new \App\CloudAccess\OldS3(
    $_ENV['OLD_S3_ENDPOINT'],
    $_ENV['OLD_S3_REGION'],
    $_ENV['OLD_S3_ACCESS_KEY'],
    $_ENV['OLD_S3_SECRET_KEY']
);

if ($listOnly) {
    echo PHP_EOL . "\033[1;36mListing all buckets in S3 Account:\033[0m" . PHP_EOL;
    $allBuckets = $oldS3->listBuckets();
    if (empty($allBuckets)) {
        echo "  Aucun bucket trouvé ou erreur." . PHP_EOL;
    } else {
        foreach ($allBuckets as $b) {
            echo "  - $b" . PHP_EOL;
        }
    }
    echo PHP_EOL;
    exit(0);
}

if (!$typeAlias) {
    echo "\033[1;31mErreur:\033[0m Le paramètre -t <type> est obligatoire." . PHP_EOL;
    echo "\033[1mUsage:\033[0m php src/test-script.php -t <type> <key>" . PHP_EOL;
    echo "       php src/test-script.php -l (listing des buckets)" . PHP_EOL;
    exit(1);
}

if (!$key) {
    echo "\033[1;31mErreur:\033[0m La clé (key) est obligatoire." . PHP_EOL;
    echo "\033[1mUsage:\033[0m php src/test-script.php -t <type> <key>" . PHP_EOL;
    exit(1);
}

// Mapping alias -> Type enum
$typeAliases = [
    'acte'       => Type::ACTE,
    'actes'      => Type::ACTE,
    'pes_aller'  => Type::PES_ALLER,
    'pes'        => Type::PES_ALLER,
    'pes_acquit' => Type::PES_ACQUIT,
    'acquit'     => Type::PES_ACQUIT,
    'mail'       => Type::MAIL,
];

$typeAlias = strtolower(trim($typeAlias));
if (!isset($typeAliases[$typeAlias])) {
    echo "\033[1;31mType inconnu:\033[0m '$typeAlias'" . PHP_EOL;
    echo "Types valides: " . implode(', ', array_keys($typeAliases)) . PHP_EOL;
    exit(1);
}

$type = $typeAliases[$typeAlias];

// -------------------------------------------------------------------------
// BUCKET LIST BUILDER (même logique que BucketResolver)
// -------------------------------------------------------------------------

function getBucketsForType(Type $type, string $key): array
{
    return match ($type) {
        Type::ACTE => getActesBuckets(),
        Type::PES_ALLER => getPesAllerBuckets($key),
        Type::PES_ACQUIT => ['sladullact-helios-pesacquitprefix'],
        Type::MAIL => ['sladullact-mail'],
    };
}

function getActesBuckets(): array
{
    $buckets = [];
    // Tous les buckets par année (2026 -> 2007)
    for ($y = (int)date('Y'); $y >= 2008; $y--) {
        $buckets[] = "sl-adullact-actes-{$y}";
    }
    $buckets[] = 'sl-adullact-actes2007'; // Cas particulier sans tiret
    $buckets[] = 'sladullact-actes';       // Fallback global
    return $buckets;
}

function getPesAllerBuckets(string $key): array
{
    $allHex = ['0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f'];
    $prefix = 'sladullact-helios-aller-file';

    // Extraire le sha1 (partie après le dernier "/")
    $parts = explode('/', $key);
    $sha1 = end($parts);
    $firstChar = strtolower(substr($sha1, 0, 1));

    // Priorité : bucket calculé en premier, puis les autres
    $buckets = ["{$prefix}{$firstChar}"];
    foreach ($allHex as $hex) {
        if ($hex !== $firstChar) {
            $buckets[] = "{$prefix}{$hex}";
        }
    }
    return $buckets;
}

// -------------------------------------------------------------------------
// RECHERCHE
// -------------------------------------------------------------------------

echo PHP_EOL;
echo "\033[1;36m╔══════════════════════════════════════════════════╗\033[0m" . PHP_EOL;
echo "\033[1;36m║           S3 Bucket Diagnostic Tool              ║\033[0m" . PHP_EOL;
echo "\033[1;36m╚══════════════════════════════════════════════════╝\033[0m" . PHP_EOL;
echo PHP_EOL;
echo "\033[1mKey:\033[0m   $key" . PHP_EOL;
echo "\033[1mType:\033[0m  {$type->value}" . PHP_EOL;
echo PHP_EOL;

if ($bucketOption) {
    echo "\033[1mForced Bucket:\033[0m $bucketOption" . PHP_EOL;
    $bucketsToTest = [$bucketOption];
} else {
    $bucketsToTest = getBucketsForType($type, $key);
    echo "\033[1mBuckets à tester:\033[0m " . count($bucketsToTest) . PHP_EOL;
}
echo str_repeat('─', 60) . PHP_EOL;

$found = false;

foreach ($bucketsToTest as $bucketName) {
    echo "  Testing \033[33m{$bucketName}\033[0m ... ";

    try {
        // headObject retourne les métadonnées si le fichier existe
        $result = $oldS3->getS3Client()->headObject([
            'Bucket' => $bucketName,
            'Key'    => $key
        ]);

        $size = $result['ContentLength'] ?? '?';
        $lastModified = $result['LastModified'] ?? '?';
        $storageClass = $result['StorageClass'] ?? 'STANDARD';
        $contentType = $result['ContentType'] ?? '?';
        $etag = $result['ETag'] ?? '?';

        echo "\033[1;32m✔ TROUVÉ\033[0m" . PHP_EOL;
        echo PHP_EOL;
        echo str_repeat('═', 60) . PHP_EOL;
        echo "\033[1;32m  RÉSULTAT : FICHIER TROUVÉ\033[0m" . PHP_EOL;
        echo str_repeat('═', 60) . PHP_EOL;
        echo "  \033[1mBucket:\033[0m         $bucketName" . PHP_EOL;
        echo "  \033[1mKey:\033[0m            $key" . PHP_EOL;
        echo "  \033[1mTaille:\033[0m         " . formatBytes($size) . " ($size bytes)" . PHP_EOL;
        echo "  \033[1mDernière modif:\033[0m $lastModified" . PHP_EOL;
        echo "  \033[1mStorage Class:\033[0m  $storageClass" . PHP_EOL;
        echo "  \033[1mContent-Type:\033[0m   $contentType" . PHP_EOL;
        echo "  \033[1mETag:\033[0m           $etag" . PHP_EOL;

        if ($storageClass === 'GLACIER' || $storageClass === 'DEEP_ARCHIVE') {
            $restore = $result['Restore'] ?? null;
            echo "  \033[1;33m⚠ Fichier en archive Glacier\033[0m";
            if ($restore) {
                echo " (Restore: $restore)";
            }
            echo PHP_EOL;
        }

        echo str_repeat('═', 60) . PHP_EOL;
        $found = true;
        break;

    } catch (\Aws\S3\Exception\S3Exception $e) {
        $code = $e->getAwsErrorCode();
        if ($code === 'NotFound' || $code === '404' || $e->getStatusCode() === 404) {
            echo "\033[90m✗ non trouvé\033[0m" . PHP_EOL;
        } elseif ($code === 'NoSuchBucket') {
            echo "\033[31m✗ bucket inexistant\033[0m" . PHP_EOL;
        } else {
            echo "\033[31m✗ erreur: {$code}\033[0m" . PHP_EOL;
        }
    }
}

if (!$found) {
    echo PHP_EOL;
    echo str_repeat('═', 60) . PHP_EOL;
    echo "\033[1;31m  RÉSULTAT : FICHIER NON TROUVÉ\033[0m" . PHP_EOL;
    echo "  Testé dans " . count($bucketsToTest) . " buckets sans succès." . PHP_EOL;
    echo str_repeat('═', 60) . PHP_EOL;
}

echo PHP_EOL;

// -------------------------------------------------------------------------

function formatBytes(int $bytes): string
{
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' Go';
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' Mo';
    if ($bytes >= 1024) return round($bytes / 1024, 2) . ' Ko';
    return $bytes . ' octets';
}
