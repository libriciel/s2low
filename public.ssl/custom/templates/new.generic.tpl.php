<?php

use S2lowLegacy\Class\Helpers;

echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n" ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
    <head>
        <title><?php echo $this->doc->title ?></title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <meta name="robots" content="noindex">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
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