<?php

use S2lowLegacy\Class\PastellWrapper;
use S2lowLegacy\Class\PastellWrapperFactory;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\PastellProperties;

require_once __DIR__ . '/../../init/init.php';

list($objectInstancier, $sqlQuery) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [ObjectInstancier::class, SQLQuery::class]
    );

function verif_sae(PastellWrapper $pastell, $listdocument, $last_action)
{
    foreach ($listdocument as $document) {
        if ($document['last_action'] == $last_action) {
            echo "le document peut etre verif\n";
            $pastell->verifSAE($document['id_d']);
        }
    }
}

$sql = <<<SQL
SELECT pastell_id_e,pastell_login,pastell_password,pastell_url
FROM authorities
WHERE pastell_url NOT LIKE ''
  AND pastell_login NOT LIKE ''
  AND pastell_password NOT LIKE ''
SQL;

$list_col = $sqlQuery->query($sql);

$pastellFactory = $objectInstancier->get(PastellWrapperFactory::class);

foreach ($list_col as $col) {
    $pastellProperties = new PastellProperties();
    $pastellProperties->id_e = $col['pastell_id_e'];
    $pastellProperties->url = $col['pastell_url'];
    $pastellProperties->login = $col['pastell_login'];
    $pastellProperties->password = $col['pastell_password'];

    $pastell = $pastellFactory->getNewInstance($pastellProperties);
    //$etat='ar-recu-sae
    //$etat='send-archive';
    $etat = 'verif-sae-erreur';
    $recherche = $pastell->listDocuments('actes-generique', $etat);
    if (!empty($recherche) && !array_key_exists('error-message', $recherche)) {
        print_r($recherche);//exit;
        verif_sae($pastell, $recherche, $etat);
    }

    $recherche = $pastell->listDocuments('helios-generique');
    if (!empty($recherche) && !array_key_exists('error-message', $recherche)) {
        print_r($recherche);
        verif_sae($pastell, $recherche, $etat);
    }
}
