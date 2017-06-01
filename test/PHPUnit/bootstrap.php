<?php

define("TESTING_ENVIRONNEMENT",true);
define("TRACE_FILE_PATH","/tmp/s2low-phpunit.log");
define('HELIOS_FILES_UPLOAD_ROOT', "vfs://test/helios/");

set_include_path(__DIR__."/../../ext/" . PATH_SEPARATOR .   get_include_path());

require_once __DIR__."/../../docker-resources/define-from-environnement.php";


require_once(__DIR__."/../../init/init.php");


require_once(__DIR__."/S2lowTestCase.class.php");


