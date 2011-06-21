<?php 

function get_url(array $params) {		
	$args = $_GET;
	foreach ($params as $param => $value) {
		$args[$param] = $value;
	}
	$url = $_SERVER["PHP_SELF"] . "?" . http_build_query($args);
	return $url;
}
	