<?php echo "<?xml version=\"1.0\" encoding=\"iso-8859-15\"?>\n" ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
    <head>
        <title><?php echo $this->title ?></title>
        <meta http-equiv="content-type" content="text/html; charset=iso-8859-15" />
        <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE ?>/custom/styles/bootstrap.min.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE ?>/custom/styles/bootstrap-theme.min.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE ?>/custom/styles/style_bs.css" />
        <script src="<?php echo WEBSITE ?>/javascript/utils.js" type="text/javascript"></script>

        <?php echo $this->header ?>
    </head>
    <body>

        <div id="bandeau_s2low" class="container">
            <a href='<?php echo WEBSITE ?>'>
                <img src="<?php echo WEBSITE ?>/custom/images/bandeau_s2low.jpg"  />
            </a>
        </div>
        <div class="container">
            <div class="row">
                <div id="menu-area" class="col-md-3">
                    <div id="menu">
                        <div id="menu-header">
                            <a href="<?php echo WEBSITE_SSL ?>">Accéder au site</a><br />
                            (Certificat nécessaire)
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <h1>Pré-requis S²LOW&nbsp;</h1>
                    <h2 class="home">Navigateurs compatibles</h2>
                    <p class="home">
                        S²LOW est dévelopé principalement pour  <b>Google Chrome</b> et  <b>Mozilla Firefox</b>
                    </p>
                    <p class="home">
                        S²LOW assure la compatibilité avec :
                    <ul>
                    <li>la dernière version stable de Google Chrome.</li>
                    <li> la dernière version de Mozilla Firefox</li>
                    <li> les versions ESR de Mozilla Firefox</li>
                    </ul>
                    </p>
                    <p class="home">
                        Bien que développé pour les standards du web, le fonctionnement et l'affichage de S²LOW ne sont pas garantie :
                        <ul>
                        <li> sur d'autre versions de Google Chrome ou Mozilla Firefox</li>
                        <li> sur d'autres navigateurs (Microsoft Internet Explorer, Microsoft Edge, Apple Safari, Opera, ...)</li>
                    </ul>
                    </p>

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>