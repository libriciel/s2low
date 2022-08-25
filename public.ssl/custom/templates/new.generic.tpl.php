<?php

use S2lowLegacy\Class\Helpers;

echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n" ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
    <head>
        <title><?php echo $this->doc->title ?></title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <link rel="stylesheet" type="text/css" href="/custom/styles/bootstrap.min.css" />
        <link rel="stylesheet" type="text/css" href="/custom/styles/bootstrap-theme.min.css" />
        <link rel="stylesheet" type="text/css" href="/custom/styles/style_bs.css" />
        <script src="<?php echo Helpers::getLink("/javascript/utils.js"); ?>" type="text/javascript"></script>
        <?php echo $this->doc->header ?>
    </head>
    <body>
        <header class="navbar" role="banner">
            <div id="home-header" class="container">
                <img id="home-banner" src="/custom/images/pages_header_banner.jpg" alt="" usemap="#map" />
                <map id="map" name="map">
                    <area shape="rect" alt="Bandeau" coords="0,95,900,120" href="<?php echo WEBSITE_SSL ?>"/>
                </map>
            </div>
        </header>
        <?php
        $this->doc->includeErrors();
        echo $this->doc->body;
        if ($layout) {
            require_once($layout);
        }
        ?>
</html>