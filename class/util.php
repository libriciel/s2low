<?php 

function get_url_same_page(array $params) {
	$args = $_GET;
	foreach ($params as $param => $value) {
		$args[$param] = $value;
	}
	$url = $_SERVER["PHP_SELF"] . "?" . http_build_query($args);
	return $url;
}
	


function utf8_encode_array($array){
	if (! is_array($array)){
		return utf8_encode($array);
	}
	$result = array();
	foreach ($array as $cle => $value) {
		$result[utf8_encode($cle)] = utf8_encode_array($value);
	}
	return $result;
}


function move_uploaded_file_wrapper($filename, $destination){
	if (TESTING_ENVIRONNEMENT){
		return rename($filename,$destination);
	}
	return move_uploaded_file($filename ,$destination );
}

function is_uploaded_file_wrapper($filename){
	if (TESTING_ENVIRONNEMENT){
		return file_exists($filename);
	}
	return is_uploaded_file($filename);
}


function is_valid_email($email){
	//Ne supporte pas les adresses email en UTF8 
	//http://stackoverflow.com/questions/19522092/should-i-use-filter-var-to-validate-email
	return !!filter_var($email ,FILTER_VALIDATE_EMAIL);
}

function get_url($url_path){
	return trim("/",WEBSITE_SSL).$url_path;
}