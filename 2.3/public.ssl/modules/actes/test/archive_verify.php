<?php 
require_once(dirname(__FILE__)."/../../../../init/init-www-actes.php");

// Recuperation des variables du POST
$archive = $_FILES["archive"];

$file_name = $_FILES["archive"]["name"];

$file_path =  TEDETIS_TMP_PATH. "/$file_name";


$tmp_f = TEDETIS_TMP_PATH . "/" . uniqid();

mkdir($tmp_f);
$archive_path = $tmp_f."/".$file_name;

move_uploaded_file( $_FILES["archive"]['tmp_name'],$archive_path);

exec("tar xvzf $archive_path --directory $tmp_f");


/*$cHandle = curl_init(ACTES_CHECK_ARCHIVE_SERVLET . "?file=" . $file_name);
curl_setopt($cHandle, CURLOPT_RETURNTRANSFER, true);
curl_setopt($cHandle, CURLOPT_HEADER, false);
$ret = curl_exec($cHandle);
if ($ret === false) {
	echo "La servlet  ne répond pas";
} else {
	echo $ret;
}
curl_close($cHandle);*/