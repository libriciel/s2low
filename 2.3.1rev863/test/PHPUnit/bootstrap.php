<?php

define("TESTING_ENVIRONNEMENT",true);
define("TRACE_FILE_PATH","/tmp/s2low-phpunit.log");
define('HELIOS_FILES_UPLOAD_ROOT', "vfs://test/helios/");


require_once(__DIR__."/../../init/init.php");

require_once(PHP_UNIT_AUTOLOADER);

require_once(__DIR__."/S2lowTestCase.class.php");


