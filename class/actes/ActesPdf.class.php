<?php 

require_once __DIR__."/../../public.ssl/modules/actes/class/ActesTransaction.class.php";
require_once __DIR__ . "/IActesPdf.php";

class ActesPdf implements IActesPdf
{

    const TEXTE_NOIR = [56, 55, 55];
    const TEXTE_BLEU = [52, 60, 142];
    const BLEU_HEADER = [196, 229, 238];
    const BLEU_FONCE_BACK = [225, 241, 247];
    const BLEU_CLAIR_BACK = [245, 250, 251];

    const COLOR_ARRAY=[self::BLEU_FONCE_BACK,self::BLEU_CLAIR_BACK];

    const TAILLE_POLICE_TITRE = 18;
    const TAILLE_POLICE_COLLECTIVITE = 14;
    const TAILLE_POLICE_TITRE_PARAGRAPHE = 14;
    const TAILLE_POLICE_TABLEAU = 12;

	private $img;
    /**
     * @var array
     */
    private $colorArray;
    /**
     * @var int
     */
    private $colorIndex;

    public function __construct(string $img) {
        $this->img = $img;
  	}

  	private function setTextColor(ExtendPdf $pdf,array $colors){
	    $pdf->SetTextColor($colors[0],$colors[1],$colors[2]);
    }

    public function initPage(ExtendPdf $pdf){
        //fini de la traitment de les requêtes.
        //créer un objet pdf.
        $pdf->AddFont('Ubuntu','R','Ubuntu-R.php');
        $pdf->AddFont('Ubuntu','B','Ubuntu-B.php');

        //ajouter une page de pdf
        $pdf->AddPage();
    }


    public function printInfosCollectivite(ExtendPdf $pdf,string $texteCollectivite, string $texteUtilisateur){
        $tailleCellInfosCollectivite = $pdf->convertPixelsToMM(self::TAILLE_POLICE_COLLECTIVITE+2);

        //ajouter collectivité et utilisateur.
        $pdf->SetFont('Ubuntu','R',self::TAILLE_POLICE_COLLECTIVITE);
        $this->setTextColor($pdf,self::TEXTE_BLEU);


        //Save the current position
        $x=$pdf->GetX();
        $y=$pdf->GetY();

        $pdf->SetLineWidth(0.5);
        $pdf->SetDrawColor(self::TEXTE_BLEU[0],self::TEXTE_BLEU[1],self::TEXTE_BLEU[2]);
        $pdf->Line(
            $x,
            $y + $pdf->convertPixelsToMM(2),
            $x,
            $y+2*$tailleCellInfosCollectivite - $pdf->convertPixelsToMM(4)
    );
        $pdf->SetLineWidth(0);

        $pdf->Cell(40,$tailleCellInfosCollectivite,"Collectivité : ".$texteCollectivite,0,0,'L');
        $pdf->Ln();
        $pdf->Cell(40,$tailleCellInfosCollectivite,"Utilisateur : ".$texteUtilisateur,0,0,'L');
        $pdf->Ln();
    }

    private function writeTitreParagraphe(ExtendPdf $pdf, string $titre){
        $this->setTextColor($pdf,self::TEXTE_NOIR);
        //obtenir tous les info et commencer de les ajouter dans tableau
        $hauteurAvantTitreParagraphe = $pdf->convertPixelsToMM(25);
        $hauteurApresTitreParagraphe = $pdf->convertPixelsToMM(9);

        $taillePolice = self::TAILLE_POLICE_TITRE_PARAGRAPHE;
        $hauteurCellPolice = $pdf->convertPixelsToMM($taillePolice)+4;

        $pdf->Ln($hauteurAvantTitreParagraphe);

        $pdf->SetFont('Ubuntu','B',$taillePolice);
        $pdf->Cell(40,$hauteurCellPolice, $titre,0,1);

        $pdf->Ln($hauteurApresTitreParagraphe);
    }

    /**
     * \brief ajouter l'entête de pdf
     * \param aucun.
     * @param ExtendPdf $pdf
     */
	public function setHead(ExtendPdf $pdf)
	{
        $pdf->Image($this->img, 10, 10, 190, 26);
        $pdf->Ln(26);

        $distanceBandeauTitre = $pdf->convertPixelsToMM(20);
        $taillePoliceTitre=self::TAILLE_POLICE_TITRE;
        $hauteurBandeauTitre = $pdf->convertPixelsToMM($taillePoliceTitre+4);
        $espaceApresTitre = $pdf->convertPixelsToMM(25);

        $pdf->Ln($distanceBandeauTitre);
		$pdf->SetFont('Ubuntu','R',$taillePoliceTitre);
        $this->setTextColor($pdf,self::TEXTE_NOIR);

        $title="Bordereau d'acquittement de transaction";
		$pdf->Cell(190,$hauteurBandeauTitre,$title,0,1,'C');
		$pdf->Ln($espaceApresTitre);
	}

	public function transTable(ExtendPdf $pdf, array $contenuTableau)
	{
        $this->writeTitreParagraphe($pdf,"Paramètres de la transaction :");

	    $this->setUpTable($pdf,[80,110],['L','L']);

        foreach ($contenuTableau as $ligne){
            $pdf->myRow(array($ligne[0],$ligne[1]));
            $this->switchColor($pdf);
        }
	}

	public function fichierTable(ExtendPdf $pdf, $fichier_table)
	{
        $this->writeTitreParagraphe($pdf,"Fichiers contenus dans l'archive :");

        $this->setUpTable($pdf, [120, 35, 35], ['L', 'C', 'C'], ["Fichier", "Type", "Taille (Ko)"]);

        foreach ($fichier_table as $file){
            if ($file["posted_filename"] && $file["filename"])
            {
                $this->addCellToTable($pdf,
                        "Nom original :",
                        $file["posted_filename"],
                        $file["filetype"],$file["filesize"]);
                $this->addCellToTable($pdf,
                        "Nom métier:",
                        $file["filename"],
                        '', '');
            }
            if (!$file["posted_filename"] && $file["filename"])
            {
                $this->addCellToTable($pdf,
                        "Nom métier:",
                        $file["filename"],
                        $file["filetype"], $file["filesize"]);
            }
            $this->switchColor($pdf);
        }
	}

	public function addCellToTable(ExtendPdf $pdf, string $typeNom, $posted_filename, $filetype, $filesize){
        $pdf->SetFont('Ubuntu','R',self::TAILLE_POLICE_TABLEAU-2);
        $pdf->myRow(array($typeNom,"","" ));
        $pdf->SetFont('Ubuntu','R',self::TAILLE_POLICE_TABLEAU);
        $pdf->myRow(array($posted_filename,$filetype,$filesize ));
    }

	public function cycleTable(ExtendPdf $pdf, array $textes)
	{
        $this->writeTitreParagraphe($pdf,"Cycle de vie de la transaction :");

        $this->setUpTable($pdf,
            array(55, 65, 70),
            array('L', 'L', 'L'),
            array("Etat", "Date", "Message"));

		foreach ($textes as $texte){
			$pdf->myRow(array($texte[0],$texte[1],$texte[2]));
            $this->switchColor($pdf);
		}
	}

    /**
     * @param ExtendPdf $pdf
     * @param array $w
     * @param array $a
     * @param array $titles
     */
    public function setUpTable(ExtendPdf $pdf, array $w, array $a, array $titles =null): void
    {
        $taillePolice = self::TAILLE_POLICE_TABLEAU;

        $this->colorIndex=0;

        $pdf->SetMyWidths($w);
        $pdf->SetMyAligns($a);
        $borders = array_fill(0,count($w),0);
        $pdf->SetMyBorder($borders);

        if(!is_null($titles)){
            $pdf->SetFont('Ubuntu', 'B', $taillePolice);
            $pdf->setMyFillcolor(array(self::BLEU_HEADER, self::BLEU_HEADER, self::BLEU_HEADER));
            $pdf->myRow($titles);
        }

        $pdf->SetFont('Ubuntu', 'R', $taillePolice);
        $pdf->SetMyBorder(array('0', '0', '0'));

        $color = $this::COLOR_ARRAY[$this->colorIndex];
        $pdf->setMyFillcolor(array($color,$color,$color));
    }

    private function switchColor($pdf){
        $this->colorIndex = ($this->colorIndex + 1) %2;
        $color = $this::COLOR_ARRAY[$this->colorIndex];
        $pdf->setMyFillcolor(array($color,$color,$color));
    }
}