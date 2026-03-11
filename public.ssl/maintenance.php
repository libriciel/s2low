<?php

/**
 * \file maintenance.php
 * \brief Page de maintenance du site
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 10.08.2006
 *
 *
 * Cette page est affichée quand l'accès à la base de données
 * est impossible.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\HTMLLayoutFactory;
use S2lowLegacy\Class\LegacyObjectsManager;

/** @var HTMLLayoutFactory $htmlLayoutFactory */
$htmlLayoutFactory = LegacyObjectsManager::getObject(HTMLLayoutFactory::class);

$doc = $htmlLayoutFactory->createLayout();

$doc->setTitle(WEBSITE_TITLE);

$html = "<div id=\"content\">";
$html .= " <h2>Maintenance en cours</h2>\n";
$html .= " Merci de tenter de vous reconnecter ultérieurement.\n";
$html .= "</div>\n";

$doc->addBody($html);

//$doc->buildFooter();

$doc->display();
