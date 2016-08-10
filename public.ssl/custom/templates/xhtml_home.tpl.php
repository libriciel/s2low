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
                <img id="home-subbanner" src="<?php echo WEBSITE ?>/custom/images/home_subbanner.jpg" alt="" />
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
                    <h1>Bienvenue sur la plate-forme S²LOW&nbsp;-&nbsp;ADULLACT</h1>
                    <h2 class="home">Offre <img src="<?php echo WEBSITE ?>/custom/images/logo_s2low.jpg" title="SLOW" alt="Logo Slow" /></h2>
                    <p class="home">
                    Cette plate-forme permet aux collectivités la transmission dématérialisée d'information vers les administrations centrales et s'inscrit dans le cadre du projet national de eGouvernement.<br />
                    Elle permet dès maintenant la transmission des actes aux préfectures via le protocole ACTES mis en place par le MIOCT.<br />
                    Les transmissions d'information vers le Trésor Public (états de paye, titres de recette, mandats de dépenses et les factures, pièces justificatives des marchés publics), HELIOS, sont réalisables via notre plate-forme.<br />

                    L'accès aux services est réservé aux personnes autorisées disposant d'un certificat électronique à cet effet.<br /><br />

                    Note : L'offre S²LOW (Service Sécurisé Libre inter-Opérable pour la Vérification et la Validation) est développée sur la base d'un co-financement entre la SCIC SA ADULLACT Projet et la société Alternance Soft.</p>
                    <div id="mention">
                        <div id="mention_hebergement">
                            <h2 class="home">Hébergement</h2>
                            <p class="home">ADULLACT Association.</p>
                        </div>
                        <div id="mention_partenaires">
                            <h2 class="home">Partenaires</h2>
                            <p class="home">
                                <a class="icon" href="http://www.adullact.org/">
                                    <img src="<?php echo WEBSITE ?>/custom/images/logo_adullact_projet.jpg" alt="Logo Adullact Projet" />
                                </a>&nbsp;
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>