<?php
/** @var S2lowLegacy\Class\HTMLLayout $this */ echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n" ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
    <head>
        <title><?php echo $this->title ?></title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <meta name="robots" content="noindex">
        <link rel="stylesheet" type="text/css" href="/custom/styles/bootstrap5.min.css" />
        <link rel="stylesheet" type="text/css" href="/custom/styles/style_bs.css" />
        <script src="/custom/js/bootstrap.bundle.min.js" type="text/javascript"></script>
        <?php echo $this->header ?>
    </head>
    <body>
        <?php echo $this->body ?>
    </body>
</html>
