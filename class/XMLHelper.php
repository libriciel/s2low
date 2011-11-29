<?php 


	
function XML_escaping($text){
	$result = htmlspecialchars($text,ENT_NOQUOTES);
	return $result;
}
	
function XML_escaping_attribute($text){
	return htmlspecialchars($text,ENT_COMPAT);
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