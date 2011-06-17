<?php 


	
function XML_escaping($text){
	return htmlspecialchars($text,ENT_NOQUOTES,"UTF-8");
}
	
function XML_escaping_attribute($text){
	return htmlspecialchars($text,ENT_COMPAT,"UTF-8");
}
	