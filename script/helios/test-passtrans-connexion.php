<?php

require_once(__DIR__ . "/../../init/init.php");

use S2lowLegacy\Lib\SftpServiceWrapper;

$sftpServiceWrapper = new SftpServiceWrapper();

$connection = $sftpServiceWrapper->connect(
    HELIOS_PASSTRANS_SERVER,
    HELIOS_PASSTRANS_PORT,
    60
);

$sftp = $sftpServiceWrapper->login($connection, HELIOS_PASSTRANS_LOGIN, HELIOS_PASSTRANS_PASSWORD);

//$entries = $sftpServiceWrapper->nlist($sftp, "depot");
//var_dump($entries);

//if ($testUpload) {
    $file_path = __DIR__ . '/../../test/PHPUnit/helios/fixtures/pes_acquit.xml';
    $destination = "MHPCE11";
    $application = "THELPES2";
    $filename = basename($file_path);
    $passtransFileName = "$destination%%$application%%$filename";
    $sftpServiceWrapper->put($sftp, "depot/$passtransFileName", $file_path);
//}

//$entries = $sftpServiceWrapper->nlist($sftp, "depot");

//var_dump($entries);

//$sftpServiceWrapper->get($sftp, "/tmp/$passtransFileName", "depot/$passtransFileName");
