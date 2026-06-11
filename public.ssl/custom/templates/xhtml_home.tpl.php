<?php

/** @var S2lowLegacy\Class\HTMLLayout $this */

use S2lowLegacy\Model\MessageAdminSQL;

echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n" ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
    <head>
        <title><?php echo $this->title ?></title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <meta name="robots" content="noindex">
        <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE ?>/custom/styles/bootstrap5.min.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE ?>/custom/styles/style_bs.css" />
        <script src="<?php echo WEBSITE ?>/javascript/utils.js" type="text/javascript"></script>
        <script src="<?php echo WEBSITE ?>/custom/js/bootstrap.bundle.min.js" type="text/javascript"></script>
        <?php echo $this->header ?>
    </head>
    <body>

        <div id="bandeau_s2low" class="container">
            <a href='<?php echo WEBSITE ?>'>
                <img src="<?php echo WEBSITE ?>/custom/images/bandeau_s2low.jpg"  alt="bandeau s2low"/>
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
                    <h1>Bienvenue sur la plate-forme S²LOW&nbsp;-&nbsp;ADULLACT</h1>
                    <h2 class="home">Offre S²LOW</h2>
                    <p class="home">
                        La plate-forme S²LOW permet aux collectivités la transmission dématérialisée d'information vers les administrations centrales :
                        elle permet ainsi la transmission des actes aux préfectures via le protocole <a href="http://www.collectivites-locales.gouv.fr/actes-0">ACTES</a> mis en place par le ministère de l'Intérieur,
                        ainsi que les transmissions d'information vers le Trésor Public
                        (états de paye, titres de recette, mandats de dépenses et les factures, pièces justificatives des marchés publics)
                        via le protocole <a href="http://www.collectivites-locales.gouv.fr/helios-lapplication-informatique-direction-generale-des-finances-publiques-dediee-au-secteur-local-0">HELIOS</a>.</p>
                    <p class="home">
                        L'accès aux services est réservé aux personnes autorisées disposant d'un <a href="http://faq.adullact.org/general/16-certificats-electroniques-et-dispositifs-de-teletransmission">certificat électronique</a> à cet effet.
                    </p>
                    <?php

                    $objectInstancier  = \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier();
                    /** @var MessageAdminSQL $messageAdminSQL */
                    $messageAdminSQL = $objectInstancier->get(MessageAdminSQL::class);
                    $messageAdmin = $messageAdminSQL->getPublishedMessage();
                    if ($messageAdmin->message_id) {
                        $messageAdmin->displayMessage();
                    }

                    ?>


                    <div id="mention">
                        <div id="mention_hebergement">
                            <h2 class="home">Opérateur</h2>
                            <p class="home"><a href="http://adullact.org/">Association ADULLACT</a>, tiers opérateur homologué</p>

                        </div>
                        <div id="mention_partenaires">
                            <h2 class="home">Partenaires</h2>
                            <p class="home">
                                <a href="http://www.libriciel.fr/">LIBRICIEL SCOP S.A.</a>, financeur et mainteneur de la solution
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
