<?php
require_once(dirname(__FILE__).'/../../../../config/config.php');
$filename=urldecode($_GET['filename']);
$root=$_GET['root'];
	
switch(strrchr(basename($filename), ".")) {

    case ".gz": $type = "application/x-gzip"; break;
    case ".tgz": $type = "application/x-gzip"; break;
    case ".zip": $type = "application/zip"; break;
    case ".pdf": $type = "application/pdf"; break;
    case ".png": $type = "image/png"; break;
    case ".gif": $type = "image/gif"; break;
    case ".jpg": $type = "image/jpeg"; break;
    case ".txt": $type = "text/plain"; break;
    case ".htm": $type = "text/html"; break;
    case ".html": $type = "text/html"; break;
    
    default: $type = "application/octet-stream"; break;
}

$file=MAIL_FILES_UPLOAD_ROOT.$root.'/'.$filename;
header("Content-Type: $type");
header("Pragma: public");
header("Content-Length: ".filesize($file));
header('Content-Disposition: attachment; filename="'.$filename.'"');
header("Content-Description: File Transfert");
readfile($file);
