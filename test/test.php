<?php
$zipArchive = new ZipArchive();
$zipArchive->open("/tmp/toto.zip",ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);

$zipArchive->addFile("/etc/passwd");

$zipArchive->close();