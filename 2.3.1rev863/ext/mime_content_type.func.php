<?php 


if (! function_exists("mime_content_type")) {
	
//La fonction mime_content_type semble ne pas exister sur 
// certaine installation du paquet PHP5 d'Ubuntu 8.04
// L'implémentation suivante ne fonctionne que sous Linux (commande file)

function mime_content_type ($filename) {
    
	$filename = escapeshellarg($filename);
	
	Trace::wrap_exec("file -bi $filename 2> /dev/null",$out,$ret);

	$t = explode(" ",$out[0]);
	
	$out = $t[0];
	
	if (empty($out)) {
       return 'application/octet-stream';
	}
	return trim($out);
}
	
}