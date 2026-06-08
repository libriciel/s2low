<?php

use S2low\Infrastructure\XML\RootFinder\XMLRootFinder;
use S2low\Services\XMLFromDGFiP\ParsablesPes\PesDocumentType;
use S2low\Services\XMLFromDGFiP\Parser;
use S2lowLegacy\Class\LegacyObjectsManager;

require_once(__DIR__ . '/../../init/init.php');

$filePath = $argv[1];

echo "Analyse de $filePath\n";

/** @var Parser $parser */
$parser = LegacyObjectsManager::getObject(Parser::class);

/** @var Parser $parser */
$xmlRootFinder = LegacyObjectsManager::getObject(XMLRootFinder::class);

try {
    if (! file_exists($filePath)) {
        throw new RuntimeException('File not found: ' . $filePath);
    }
    $file = new SplFileObject($filePath);
    $rootElementName = $xmlRootFinder->getRootElementName($file);
    $pesDocumentType = PesDocumentType::fromOrUnknown($rootElementName);

    [$extractedValues,$errors ] = $parser->parsePesFile(
        $file,
        $pesDocumentType->getKeysToExtract(),
        $pesDocumentType->getXsdPath(),
    );

    var_dump($extractedValues);
    var_dump($errors);
} catch (Exception $e) {
    echo $e->getMessage() . '\n';
} finally {
    $status = file_get_contents('/proc/' . getmypid() . '/status');
    var_dump($status);
    /* Currently used memory */
    $mem_usage = memory_get_usage(true);
    /* Peak memory usage */
    $mem_peak = memory_get_peak_usage(true);
    echo 'The script is now using: ' . round($mem_usage / 1024) . 'KB of memory\n';
    echo 'Peak usage:' . round($mem_peak / 1024) . 'KB of memory.\n';
}
