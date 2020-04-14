<?php




class BordereauPdfGenerator
{
    /**
     * @var ExtractDataForBordereauPDF
     */
    private $extractDataForBordereauPDF;
    /**
     * @var IActesPdf
     */
    private $actesPdf;

    public function __construct(ExtractDataForBordereauPDF $extractDataForBordereauPDF, IActesPdf $actesPdf)
    {
        $this->extractDataForBordereauPDF= $extractDataForBordereauPDF;
        $this->actesPdf = $actesPdf;
    }
    public function generate($transactionId,$output,$addEmailNotificationField,$out="I"){
        $data = $this->extractDataForBordereauPDF->extract($transactionId,$addEmailNotificationField);

        $pdf=new ExtendPdf();

        $this->create_pdf($pdf,$data,$output,$out);
        return $pdf->Output($output.".pdf",$out);
    }

    /**
     * @param ExtendPdf $pdf
     * @param DataForBordereauPDF $data
     * @param string $title le nom du fichier SANS l'extension PDF
     * @param string $out - voir la fonction FPDF Output
     * @return string
     */

    public function create_pdf(ExtendPdf $pdf, DataForBordereauPDF $data,string $title, string $out ="I") {

        $this->actesPdf->initPage($pdf);
        //définir l'entête de page.
        $this->actesPdf->set_head($pdf);

        $this->actesPdf->printInfosCollectivite(
            $pdf,
            $data->getTexteCollectivite(),
            $data->getTexteUtilisateur());
        // imprimé la table de  transaction

        $this->actesPdf->trans_table(
            $pdf,
            $data->getContenuTableau());

        //$this->writeTitreParagraphe("Fichiers contenus dans l'archive :");
        // imprimé la talbe de Fichier calcule dans l'archivage
        $this->actesPdf->fichier_table(
            $pdf,
            $data->getFichierTable());

        //$this->writeTitreParagraphe("Cycle de vie de la transaction :");
        //imprimé la table de cycle
        $this->actesPdf->cycle_table(
            $pdf,
            $data->getCycleTable());

        // imprimé la notification de la transaction:
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(40,10,"",0,1);
    }
}