<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\ExtendPdf;

interface IActesPdf
{
    public function initPage(ExtendPdf $pdf);

    public function printInfosCollectivite(ExtendPdf $pdf, string $texteCollectivite, string $texteUtilisateur);

    /**
     * \brief ajouter l'entête de pdf
     * \param aucun.
     * @param ExtendPdf $pdf
     */
    public function setHead(ExtendPdf $pdf);

    public function transTable(ExtendPdf $pdf, array $contenuTableau);

    public function fichierTable(ExtendPdf $pdf, $fichier_table);

    public function cycleTable(ExtendPdf $pdf, array $textes);
}
