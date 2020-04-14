<?php

interface IActesPdf
{
    public function initPage(ExtendPdf $pdf);

    public function printInfosCollectivite(ExtendPdf $pdf, string $texteCollectivite, string $texteUtilisateur);

    /**
     * \brief ajouter l'entête de pdf
     * \param aucun.
     * @param ExtendPdf $pdf
     */
    public function set_head(ExtendPdf $pdf);

    public function trans_table(ExtendPdf $pdf, array $contenuTableau);

    public function fichier_table(ExtendPdf $pdf, $fichier_table);

    public function cycle_table(ExtendPdf $pdf, array $textes);
}