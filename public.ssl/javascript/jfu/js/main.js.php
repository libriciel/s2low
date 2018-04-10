<?php

header("Content-type: application/javascript");

include "../../../../config/config.php";
echo 'var s2lowMaxFileSize = ' . ACTES_MAX_BATCH_UPLOAD_SIZE . ';';

include "main.js";
