<?php

require_once(__DIR__ . "/../../init/init.php");
\S2lowLegacy\Class\LegacyObjectsManager::setLegacyObjectInstancier();


$archive = new \Libriciel\LibActes\Archive();

$archive_info  = $archive->getArchiveDataFromFolder($argv[1]);

print_r($archive_info);
