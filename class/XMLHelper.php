<?php 


	
function XML_escaping($text){
	return htmlspecialchars($text,ENT_NOQUOTES,"UTF-8");
}
	
function XML_escaping_attribute($text){
	return htmlspecialchars($text,ENT_COMPAT,"UTF-8");
}
	
function cp1252_to_iso88591($text) {
	$cp1252_map = array(
						"&#8211;"  => "-",
						"&#8212;"  => "-",
						"&#8216;"  => "'",
						"&#8217;"  => "'",
						"&#8218;"  => ",",
						"&#8220;"  => '"',
						"&#8221;"  => '"',
						"&#8222;"  => '"',
						"&#8224;"  => '"',
						"&#8230;"  => "..."
					);
	return strtr($text, $cp1252_map);
}