<?php

require_once("init-test-actes-api.php");

echo $testApi->get('actes_transac_get_status.php?transaction=18');
