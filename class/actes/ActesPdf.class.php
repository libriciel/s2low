<?php 

require_once __DIR__."/../../public.ssl/modules/actes/class/ActesTransaction.class.php";

class ActesPdf {

    const TEXTE_NOIR = [56, 55, 55];
    const TEXTE_BLEU = [52, 60, 142];
    const BLEU_HEADER = [196, 229, 238];
    const BLEU_CLAIR_BACK = [225, 241, 247];
    const BLEU_FONCE_BACK = [245, 250, 251];

	/**
	 * @var ExtendPDF
	 */
	private $pdf;
	private $actesTransaction;
	private $user;
	
	private $img;

	private $addEmailNotificationField;
	
	public function __construct() {
        $this->img = SITEROOT . "public.ssl/custom/images/bandeau-s2low-190.jpg";
  	}

  	public function addEmailNotificationField(){
  		$this->addEmailNotificationField = true;
  	}

  	public function setTextColor(array $colors){
	    $this->pdf->SetTextColor($colors[0],$colors[1],$colors[2]);
    }
  	
	public function create_pdf($transaction_id) {
        $trans = new ActesTransaction();
        $trans->setId($transaction_id);
        $trans->init();
        $envelope = new ActesEnvelope($trans->get("envelope_id"));
        $envelope->init();

        $owner = new User($envelope->get("user_id"));
        $owner->init();


        $this->actesTransaction=$trans;
        $this->user = $owner;

		$author = new Authority($this->user->get("authority_id"));
		$author->init();
		
		//fini de la traitment de les requêtes.
		//créer un objet pdf.
		$this->pdf=new ExtendPdf();
        $this->pdf->AddFont('Ubuntu','R','Ubuntu-R.php');
        $this->pdf->AddFont('Ubuntu','B','Ubuntu-B.php');

		//ajouter une page de pdf
		$this->pdf->AddPage();
		
		//définir l'entête de page.
		$this->set_head();
		
		//ajouter cllectivité et utilisateur.
		$this->pdf->SetFont('Ubuntu','R',12);
		$this->setTextColor(self::TEXTE_BLEU);


		$texteCollectivite = "Collectivité : ".$author->get("name");
		$texteUtilisateur = "Utilisateur : ".$this->user->get("name")." ".$this->user->get("givenname");

        $this->pdf->Ln();
        $this->pdf->Cell(40,10,$texteCollectivite,0,0,'L');
        $this->pdf->Ln();
        $this->pdf->Cell(40,10,$texteUtilisateur,0,0,'L');
        $this->pdf->Ln();
	
		// imprimé la table de  transaction
		$this->setTextColor(self::TEXTE_NOIR);
		$this->trans_table($this->actesTransaction);
		$this->pdf->Cell(40,10,"",0,1);
		
		// imprimé la talbe de Fichier calcule dans l'archivage
		$this->fichier_table($this->actesTransaction);
		
		//imprimé la table de cycle
		$this->pdf->Cell(40,10,"",0,1);
		$this->cycle_table($this->actesTransaction);
		$this->pdf->Cell(40,10,"",0,1);
		
		// imprimé la notification de la transaction:
		$this->pdf->SetFont('Arial','',12);
		$this->pdf->Cell(40,10,"",0,1);
	}

	/**
	 * @param string $title le nom du fichier SANS l'extension PDF
	 * @param string $out - voir la fonction FPDF Output
	 * @return string
	 */
	public function output($title,$out = "I"){
		return $this->pdf->Output($title.".pdf",$out);
	}
	
	/**
  * \brief ajouter l'entête de pdf
  * \param aucun.
  * 
  */
	protected function set_head()
	{
        $this->pdf->Image($this->img, 10, 10, 190, 26);

		$this->pdf->Ln(40);
		$this->pdf->SetFont('Ubuntu','R',16);
        $this->setTextColor(self::TEXTE_NOIR);

		$this->pdf->Cell(20);
        $title="Bordereau d'acquittement de transaction";
		$this->pdf->Cell(140,6,$title,0,1);
		$this->pdf->Ln(7);
	}
	
	
	private function getNotifieA(ActesTransaction $trans){
		if ($trans->get("broadcasted") == 't' ) {
      		return "Notifiée à " . $trans->get("broadcast_emails");
		}
		if ($this->addEmailNotificationField && $trans->get("broadcast_emails")){
			return "Notifiée à " . $trans->get("broadcast_emails");
		}
    	return "Non notifiée";
	}

	protected function trans_table(ActesTransaction $trans)
	{
		//traiter des requêtes
		$transactionTypes = $trans->get("transactionTypes");
		$transNatures = ActesTransaction :: getTransactionNaturesIdDescr();
		
		if(isset($transNatures[$trans->get("nature_code")])){
			$nature_description = $transNatures[$trans->get("nature_code")];
		} else {
			$nature_description = "n/a";
		}
		
		$notification = $this->getNotifieA($trans);

		$classifcation = $trans->get("classification") ;
		$classifcation_string = $trans->get('classification_string');
		if ($classifcation_string) {
			$classifcation .= " - $classifcation_string";
		}
      		
      	$arch_url = $trans->get("archive_url");
		if (empty($arch_url))
			$arch_url= "Non définie";

		$contenuTableau =[
		["Type de transaction:",$transactionTypes[$trans->get("type")]],
		["Nature de l'acte:",$nature_description],
		["Numéro de l'acte:",$trans->get("number")],
		["Date de la décision:",$trans->get("decision_date")],
		["Objet:",$trans->get("subject")],
        ["Documents papiers complémentaires:",$trans->getDocumentPapier()?"OUI":"NON"],
        ["Classification matières/sous-matières:",$classifcation],
		["Identifiant unique:",$trans->get("unique_id")],
		["URL d'archivage:",$arch_url],
		["Notification:",$notification]
		];

        //obtenir tous les info et commencer de les ajouter dans tableau
        $this->pdf->SetFont('Ubuntu','B',12);
        $this->pdf->Cell(40,10,"Paramètre de la transaction :",0,1);
        $this->pdf->SetFont('Ubuntu','R',10);


        $this->pdf->SetMyWidths(array(10,70,80));
        $this->pdf->SetMyAligns(array('0','L','L'));
        $this->pdf->SetMyBorder(array('0','0','0'));

        $colorIndex=0;
        $colorArray=[self::BLEU_FONCE_BACK,self::BLEU_CLAIR_BACK];

		foreach ($contenuTableau as $ligne){
		    $color = $colorArray[$colorIndex];
            $this->pdf->setMyFillcolor(array($color,$color,$color));
            $this->pdf->myRow(array("",$ligne[0],$ligne[1]));
            $colorIndex = ($colorIndex +1) %2;
        }
	}

	public function fichier_table(ActesTransaction $trans)
	{
		//traiter des requêtes
		$files = $trans->fetchFilesList();
		
		//obtenir tous les info et commencer de les ajouter dans tableau
		$this->pdf->SetFont('Ubuntu','B',12);
		$this->pdf->Cell(40,10,"Fichier contenus dans l'archive :",0,1);

		$this->pdf->SetMyWidths(array(2,100,40,50));
		$this->pdf->SetMyAligns(array('C','L','C','C'));
		$this->pdf->SetMyBorder(array('0','0','0','0'));

		$this->pdf->SetFont('Ubuntu','B',10);
		$this->pdf->setMyFillcolor(array(array(255,255,255),self::BLEU_HEADER,self::BLEU_HEADER,self::BLEU_HEADER));
		$this->pdf->myRow(array("","Fichier","Type de fichier","Taille du fichier"));

		$this->pdf->SetFont('Ubuntu','R',10);
		$this->pdf->SetMyBorder(array('0','0','0','0'));

        $colorIndex=0;
        $colorArray=[self::BLEU_FONCE_BACK,self::BLEU_CLAIR_BACK];

		foreach ($files as $file)
		{
		    $color = $colorArray[$colorIndex];
            $this->pdf->setMyFillcolor(array(array(255,255,255),$color,$color,$color));
			// c'est pas dans tous les cas on as de fichier posted ou fichier normal.
			if ($file["posted_filename"])
			{
				$this->pdf->myRow(array("","nom de original:","","" ));
				$this->pdf->myRow(array("",$file["posted_filename"],$file["mimetype"],$file["size"] ));
                $colorIndex = ($colorIndex + 1) %2;
			}
            $color = $colorArray[$colorIndex];
            $this->pdf->setMyFillcolor(array(array(255,255,255),$color,$color,$color));

			if ($file["name"])
			{
				$this->pdf->myRow(array("","nom de métier:\n","",""));
				$this->pdf->myRow(array("",$file["name"],$file["mimetype"],$file["size"]));
                $colorIndex = ($colorIndex + 1) %2;
			}
		}
	}

	public function cycle_table(ActesTransaction $trans)
	{
		//traiter des requêtes
		$workflow = $trans->fetchWorkflow();
		$status = ActesTransaction :: getStatusList();
		
		$this->pdf->SetFont('Ubuntu','B',12);
		$this->pdf->Cell(40,10,"Cycle de vie de la transaction :",0,1);

		$this->pdf->SetMyWidths(array(10,50,60,60));
		$this->pdf->SetMyAligns(array('C','L','C','C'));
		$this->pdf->SetMyBorder(array('0','0','0','0'));
		$this->pdf->SetFont('Ubuntu','B',10);

		$this->pdf->setMyFillcolor(array(array(255,255,255),self::BLEU_HEADER,self::BLEU_HEADER,self::BLEU_HEADER));
		$this->pdf->myRow(array("","Etat","Date", "Message"));
		$this->pdf->SetFont('Ubuntu','R',10);
		$this->pdf->SetMyBorder(array('0','0','0','0'));

        $colorIndex=0;
        $colorArray=[self::BLEU_FONCE_BACK,self::BLEU_CLAIR_BACK];

		foreach ($workflow as $stage)
		{
            $color = $colorArray[$colorIndex];
            $this->pdf->setMyFillcolor(array(array(255,255,255),$color,$color,$color));

			$this->pdf->myRow(array("",$status[$stage["status_id"]],Helpers :: getDateFromBDDDate($stage["date"], true),$stage["message"]));

            $colorIndex = ($colorIndex + 1) %2;
		}
	}
}