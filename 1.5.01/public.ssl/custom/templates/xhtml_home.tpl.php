<?php echo "<?xml version=\"1.0\" encoding=\"iso-8859-15\"?>\n" ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
    <head>
        <title><?php echo $this->title ?></title>
        <meta http-equiv="content-type" content="text/html; charset=iso-8859-15" />
        <link rel="stylesheet" type="text/css" href="/custom/styles/bootstrap.min.css" />
        <link rel="stylesheet" type="text/css" href="/custom/styles/bootstrap-theme.min.css" />
        <link rel="stylesheet" type="text/css" href="/custom/styles/style_bs.css" />
        <?php echo $this->header ?>
    </head>
    <body> 
        <div id="header" class="navbar">
            <div id="home-header" class="container">
                <img id="home-banner" src="<?php echo WEBSITE ?>/custom/images/home_banner.jpg" alt="" usemap="#map" />
                <map id="map" name="map"><area shape="rect" alt="Bandeau" coords="0,120,900,180" href="<?php echo WEBSITE ?>"/></map>
                <!--<img id="home-subbanner" src="<?php echo WEBSITE ?>/custom/images/home_subbanner.jpg" alt="" />-->
            </div>
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
                    <p>Conçue  pour répondre aux enjeux de la dématérialisation des procédures et des  démarches administratives, la plateforme <strong>bl-echanges-securises.fr </strong>vous  permet dès à présent&nbsp;de&nbsp;:</p>
                    <ul>
                        <li>certifier, à l'aide de votre signature électronique, toutes vos  informations numériques&nbsp;: données de gestion, mails et plus largement tout  formulaire ou document bureautique,</li>
                    </ul>
                    <ul>
                        <li>transmettre, en toute sécurité, ces informations sous forme  électronique à vos partenaires et fournisseurs&nbsp;:</li>
                        <ul>
                            <li>soit en respectant les protocoles définis au plan national :</li>
                            <ul>
                                <li>ACTES, pour la transmission au contrôle de légalité de vos  délibérations, décisions et arrêtés,</li>
                                <li>PES V2, pour la transmission des informations comptables et budgétaires au logiciel HELIOS,</li>
                            </ul>
                            <li>soit en utilisant notre service de mail sécurisé,</li>
                        </ul>
                    </ul>
                    <ul>
                        <li>suivre tous vos envois grâce aux accusés de réception délivrés par <strong>bl-echanges-securises.fr</strong>.</li>
                    </ul>
                    <p>Progressivement,  si vous êtes utilisateur de progiciels édités par Berger-Levrault, l'accès à  cette plateforme et son utilisation seront réalisés directement depuis nos  solutions.</p>
                    <p>Pour  accéder à ces services d'échanges et de transmissions sécurisés, vous devez  disposer d'un certificat électronique.</p>
                </div>
            </div>
        </div>
    </body>
</html>