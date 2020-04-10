<?php

interface IActesPdf
{
    /**
     * @param DataForBordereauPDF $data
     * @param string $title le nom du fichier SANS l'extension PDF
     * @param string $out - voir la fonction FPDF Output
     * @return string
     */
    public function create_pdf(DataForBordereauPDF $data, string $title, string $out = "I");
}