<?php echo "<?xml version=\"1.0\" encoding=\"iso-8859-15\"?>\n" ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
    <head>
        <title><?php echo $this->title ?></title>
        <meta http-equiv="content-type" content="text/html; charset=iso-8859-15" />
        <link rel="stylesheet" type="text/css" href="/custom/styles/bootstrap.min.css" />
        <link rel="stylesheet" type="text/css" href="/custom/styles/bootstrap-theme.min.css" />
        <link rel="stylesheet" type="text/css" href="/custom/styles/style_bs.css" />
        <script src="<?php echo WEBSITE_SSL ?>/javascript/utils.js" type="text/javascript"></script>
        <?php echo $this->header ?>
    </head>
    <body>
        <div id="header" class="navbar">
            <div id="home-header" class="container">
                <img id="home-banner" src="/custom/images/pages_header_banner.jpg" alt="" usemap="#map" />
                <map id="map" name="map"><area shape="rect" alt="Bandeau" coords="0,95,900,120" href="<?php echo WEBSITE_SSL ?>"/></map>
            </div>
        </div>

