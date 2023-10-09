<?php

use S2lowLegacy\Class\CloudStorage;
use S2lowLegacy\Class\CloudStorageFactory;
use S2lowLegacy\Class\helios\PESRetourCloudStorage;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\HeliosRetourSQL;

require_once(__DIR__ . "/../../init/init.php");
list( $sqlQuery, $cloudStorageFactory,$heliosRetourSQL ) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [ SQLQuery::class, CloudStorageFactory::class, HeliosRetourSQL::class]
    );

/** @var  HeliosRetourSQL $pesRetours */
$pesRetours =  $heliosRetourSQL->getAllPESRetour();

/* @var CloudStorage $pesRetourCloudStorage */
$pesRetourCloudStorage = $cloudStorageFactory->getInstanceByClassName(PESRetourCloudStorage::class);

foreach ($pesRetours as $pesRetour) {
    echo "------------------------------\n";
    echo "id : {$pesRetour["id"]} ; is_in_cloud : {$pesRetour["is_in_cloud"]} ; not_available : {$pesRetour["not_available"] }  \n";
    try {
        list($filesize, $sha1_file) = $pesRetourCloudStorage->getSize($pesRetour["id"]);
        echo "$filesize, $sha1_file\n";
        $heliosRetourSQL->setSize($pesRetour["id"], $filesize, $sha1_file);
    } catch (Exception $e) {
        var_dump($e->getMessage());
        #do nothing
    }
}
