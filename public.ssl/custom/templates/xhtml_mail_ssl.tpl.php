<?php

/** @var S2lowLegacy\Class\HTMLLayout $this */

use S2lowLegacy\Class\Helpers;

echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n" ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
    <head>
        <title><?php echo $this->title ?></title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <meta name="robots" content="noindex">
        <link rel="stylesheet" type="text/css" href="/custom/styles/bootstrap.min.css" />
        <link rel="stylesheet" type="text/css" href="/custom/styles/bootstrap-theme.min.css" />
        <link rel="stylesheet" type="text/css" href="/custom/styles/style_bs.css" />
        <link rel="stylesheet" type="text/css" href="/custom/styles/style_mail.css" />
        <script src="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/javascript/utils.js"); ?>" type="text/javascript"></script>
        <?php echo $this->header ?>
    </head>
    <body>

    <div id="bandeau_s2low" class="container">
        <a href='<?php echo WEBSITE_SSL ?>'>
            <img src="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/custom/images/bandeau_s2low.jpg"); ?>"
                 alt="bandeau s2low"
            />
        </a>
    </div>

        <?php echo $this->body ?>
