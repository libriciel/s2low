<?php 

require_once __DIR__."/../../public.ssl/modules/actes/class/ActesTransaction.class.php";
require_once __DIR__ . "/IActesPdf.php";

class ActesPdfLegacy implements IActesPdf
{

	/** @var string  */
	private $img;
	
	public function __construct(string $img) {
        $this->img = $img;
  	}


    public function initPage(ExtendPdf $pdf){
        //fini de la traitment de les requêtes.
        //créer un objet pdf.
        $pdf->AddFont('Ubuntu','R','Ubuntu-R.php');
        $pdf->AddFont('Ubuntu','B','Ubuntu-B.php');

        //ajouter une page de pdf
        $pdf->AddPage();
    }

    public function printInfosCollectivite(ExtendPdf $pdf, string $texteCollectivite, string $texteUtilisateur){
        $pdf->SetFont('Arial','B',12);
        $pdf->SetTextColor(94,106,23);
        $pdf->Cell(40,10,"Collectivité :",0,0,'R');
        $pdf->Cell(40,10,$texteCollectivite,0,1,'L');
        $pdf->Cell(40,10,"Utilisateur :",0,0,'R');
        $pdf->Cell(40,10,$texteUtilisateur,0,1,'L');
    }

    /**
     * @param ExtendPdf $pdf
     * @param $titre
     * @param int $w
     */

    private function writeTitreParagraphe(ExtendPdf $pdf,string $titre,int $w){
        $pdf->SetFont('Arial','',12);
        $this->myRectangle($pdf,$w);
        $pdf->Cell(40,10,$titre,0,1);
    }

    /**
     * \brief ajouter l'entête de pdf
     * \param aucun.
     * @param ExtendPdf $pdf
     */
	public function setHead(ExtendPdf $pdf)
	{
        $title="BORDEREAU D'ACQUITTEMENT DE TRANSACTION";
        $pdf->Image($this->img, 10, 10, 190, 26);

        $pdf->Ln(40);
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(20);
        //$pdf->SetFillColor(140,207,247);

        $x=$pdf->GetX();
        $y=$pdf->GetY();
        $pdf->SetFillColor(192);
        $pdf->RoundedRect($x-2, $y-1, 150, 8, 3, 'DF', '13');
        $pdf->Cell(140,6,$title,0,1);
        $pdf->Ln(7);

	}

	public function transTable(ExtendPdf $pdf, array $contenuTableau)
	{
        $pdf->SetTextColor(40,36,94);
        $this->writeTitreParagraphe($pdf,"Paramètre de la transaction :",60);
        //obtenir tous les info et commencer de les ajouter dans tableau
        $pdf->SetFont('Arial','i',10);
        $pdf->SetMyWidths(array(10,70,80));
        $pdf->SetMyAligns(array('0','L','L'));
        $pdf->SetMyBorder(array('0','BT','BT'));
        $pdf->setMyFillcolor(array(array(255,255,255),array(216,252,254),array(216,252,254)));
        foreach ($contenuTableau as $ligne){
            $pdf->myRow(array("",$ligne[0],$ligne[1]),true);
        }
        $pdf->Cell(40,10,"",0,1);
	}

	public function fichierTable(ExtendPdf $pdf, $fichier_table)
	{
        $this->writeTitreParagraphe($pdf,"Fichier contenus dans l'archive :",69);
        //obtenir tous les info et commencer de les ajouter dans tableau

        $this->setUpTable(
            $pdf,
            array(2, 100, 40, 50),
            array("", "Fichier", "Type de fichier", "Taille du fichier")
            );

        foreach ($fichier_table as $file)
        {
            if ($file["posted_filename"])
            {
                $this->addCellToTable($pdf,
                    "Nom original :",
                    $file["posted_filename"], $file["filetype"], $file["filesize"]
                );
            }
            if ($file["filename"])
            {
                $this->addCellToTable($pdf,
                    "Nom métier :",
                    $file["filename"], $file["filetype"], $file["filesize"]
                );
            }
        }
	}

	public function cycleTable(ExtendPdf $pdf, $textes)
	{
        $pdf->Cell(40,10,"",0,1);
        $this->writeTitreParagraphe($pdf,"Cycle de vie de la transaction :",65);

        $this->setUpTable($pdf,
            array(10, 50, 60, 60),
            array("", "Etat", "Date", "Message")
        );

        foreach ($textes as $texte)
        {
            $pdf->myRow(array("",$texte[0],$texte[1],$texte[2]),true);
        }
        $pdf->Cell(40,10,"",0,1);
	}

    private function myRectangle(ExtendPdf $pdf, $w,$h=6)
    {
        $x=$pdf->GetX();
        $y=$pdf->GetY();
        $pdf->SetFillColor(192);

        // pour changer le style, voir le commentaire de la fonction rounderect ExtendPdf::RoundeRect()
        $pdf->RoundedRect($x-2, $y+2, $w, $h, 3, 'DF', '13');
    }

    /**
     * @param ExtendPdf $pdf
     * @param array $columnsWidths
     * @param array $columnsTitles
     */
    private function setUpTable(ExtendPdf $pdf, array $columnsWidths, array $columnsTitles=null): void
    {
        $pdf->SetMyWidths($columnsWidths);
        $pdf->SetMyAligns(array('C', 'C', 'C', 'C'));

        if(!is_null($columnsTitles)){
            $pdf->SetMyBorder(array('0', 'R', 'RL', 'L'));
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->setMyFillcolor(array(array(255, 255, 255), array(200, 220, 255), array(200, 220, 255), array(200, 220, 255)));
            $pdf->myRow($columnsTitles, true);
        }

        $pdf->SetFont('Arial', 'i', 10);
        $pdf->SetMyBorder(array('R', '1', '1', '1'));
        $pdf->setMyFillcolor(array(array(255, 255, 255), array(216, 252, 254), array(216, 252, 254), array(216, 252, 254)));
    }

    /**
     * @param ExtendPdf $pdf
     * @param string $typeNom
     * @param $posted_filename
     * @param $filetype
     * @param $filesize
     */
    public function addCellToTable(ExtendPdf $pdf,
                                   string $typeNom,
                                   string $posted_filename,
                                   string $filetype,
                                   string $filesize): void
    {
        $pdf->SetMyBorder(array('R', 'LTR', 'LTR', 'LTR'));
        $pdf->myRow(array("", $typeNom, "", ""), true);
        $pdf->SetMyBorder(array('R', 'LBR', 'LBR', 'LBR'));
        // Pour le fichier métier, on affiche la taille et le type du fichier original...
        $pdf->myRow(array("", $posted_filename, $filetype, $filesize), true);
    }
}