<?php 

require_once (SITEROOT . '/class/ExtendPdf.class.php');

class ActesPdf {

	/**
	 * @var ExtendPDF
	 */
	private $pdf;
	private $actesTransaction;
	private $user;
	
	private $img;

	private $addEmailNotificationField;
	
	public function __construct(ActesTransaction $actesTransaction,User $user) {
		$this->img = SITEROOT . "public.ssl/custom/images/home_banner.jpg"; 
  		$this->actesTransaction=$actesTransaction;
  		$this->user = $user;
  	}

  	public function addEmailNotificationField(){
  		$this->addEmailNotificationField = true;
  	}
  	
	public function create_pdf() {

		$author = new Authority($this->user->get("authority_id"));
		$author->init();
		
		//fini de la traitment de les requêtes.
		//créer un objet pdf.
		$this->pdf=new ExtendPdf();
		
		//ajouter une page de pdf
		$this->pdf->AddPage();
		
		//définir l'entête de page.
		$this->set_head();
		
		//ajouter cllectivité et utilisateur.
		$this->pdf->SetFont('Arial','B',12);
		$this->pdf->SetTextColor(94,106,23);
		$this->pdf->Cell(40,10,"Collectivité :",0,0,'R');
		$this->pdf->Cell(40,10,$author->get("name"),0,1,'L');
		$this->pdf->Cell(40,10,"Utilisateur :",0,0,'R');
		$this->pdf->Cell(40,10,$this->user->get("name")." ".$this->user->get("givenname"),0,1,'L');
	
		// imprimé la table de  transaction
		$this->pdf->SetTextColor(40,36,94);
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
		$title="BORDEREAU D'ACQUITTEMENT DE TRANSACTION";
		$this->pdf->Image($this->img, 10,10,190,30);
		$this->pdf->Ln(40);
		$this->pdf->SetFont('Arial','B',16);
		$this->pdf->Cell(20);
		//$this->pdf->SetFillColor(140,207,247);
		
		$x=$this->pdf->GetX();
		$y=$this->pdf->GetY();
		$this->pdf->SetFillColor(192);
		$this->pdf->RoundedRect($x-2, $y-1, 150, 8, 3, 'DF', '13');
		$this->pdf->Cell(140,6,$title,0,1);
		$this->pdf->Ln(7);
	}
	
	
	private function getNotifieA($trans){
		if ($trans->get("broadcasted") == 't' ) {
      		return "Notifiée à " . $trans->get("broadcast_emails");
		}
		if ($this->addEmailNotificationField && $trans->get("broadcast_emails")){
			return "Notifiée à " . $trans->get("broadcast_emails");
		}
    	return "Non notifiée";
	}
	
  /**
  * \brief ajouter la table de tansaction
  * \param $trans= objet de ActesTransaction
  * 
  */
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
	
      		
      	$arch_url = $trans->get("archive_url");
		if (empty($arch_url))
			$arch_url= "Non définie";	
			
		//obtenir tous les info et commencer de les ajouter dans tableau
		$this->pdf->SetFont('Arial','',12);
		$this->myRectangle(60);
		$this->pdf->Cell(40,10,"Paramètre de la transaction :",0,1);
		$this->pdf->SetFont('Arial','i',10);
		$this->pdf->SetMyWidths(array(10,70,80));
		$this->pdf->SetMyAligns(array('0','L','L'));
		$this->pdf->SetMyBorder(array('0','BT','BT'));
		$this->pdf->setMyFillcolor(array(array(255,255,255),array(216,252,254),array(216,252,254)));
		$this->pdf->myRow(array("","Type de transaction:",$transactionTypes[$trans->get("type")]));
		$this->pdf->myRow(array("","Nature de l'acte:",$nature_description));
		$this->pdf->myRow(array("","Numéro de l'acte:",$trans->get("number")));
		$this->pdf->myRow(array("","Date de la décision:",$trans->get("decision_date")));
		$this->pdf->myRow(array("","Objet:",$trans->get("subject")));
		$this->pdf->myRow(array("","Classification matières/sous-matières:",$trans->get("classification")));
		$this->pdf->myRow(array("","Identifiant unique:",$trans->get("unique_id")));
		$this->pdf->myRow(array("","URL d'archivage:",$arch_url));
		$this->pdf->myRow(array("","Notification:",$notification));
	}
	
	/**
  * \brief ajouter la fichier de tansaction
  * \param $trans= objet de ActesTransaction
  * 
  */
	public function fichier_table($trans)
	{
		//traiter des requêtes
		$files = $trans->fetchFilesList();
		
		//obtenir tous les info et commencer de les ajouter dans tableau
		$this->pdf->SetFont('Arial','',12);
		$this->myRectangle(69);
		$this->pdf->Cell(40,10,"Fichier contenus dans l'archive :",0,1);

		$this->pdf->SetMyWidths(array(2,100,40,50));
		$this->pdf->SetMyAligns(array('C','C','C','C'));
		$this->pdf->SetMyBorder(array('0','R','RL','L'));
		$this->pdf->SetFont('Arial','B',10);
		$this->pdf->setMyFillcolor(array(array(255,255,255),array(200,220,255),array(200,220,255),array(200,220,255)));
		$this->pdf->myRow(array("","Fichier","Type de fichier","Taille du fichier"));
		$this->pdf->SetFont('Arial','i',10);
		$this->pdf->SetMyBorder(array('R','1','1','1'));
		$this->pdf->setMyFillcolor(array(array(255,255,255),array(216,252,254),array(216,252,254),array(216,252,254)));
		foreach ($files as $file)
		{
			// c'est pas dans tous les cas on as de fichier posted ou fichier normal.
			if ($file["posted_filename"])
			{
				$this->pdf->SetMyBorder(array('R','LTR','LTR','LTR'));
				$this->pdf->myRow(array("","nom de original:","","" ));
				$this->pdf->SetMyBorder(array('R','LBR','LBR','LBR'));			
				$this->pdf->myRow(array("",$file["posted_filename"],$file["mimetype"],$file["size"] ));
			}
			if ($file["name"])
			{
				$this->pdf->SetMyBorder(array('R','LTR','LTR','LTR'));
				$this->pdf->myRow(array("","nom de métier:\n","",""));
				$this->pdf->SetMyBorder(array('R','LBR','LBR','LBR'));	
				$this->pdf->myRow(array("",$file["name"],$file["mimetype"],$file["size"]));
			}
		}	
	}
	
  /**
  * \brief ajouter la table de cycle
  * \param $trans= objet de ActesTransaction
  * 
  */
	public function cycle_table($trans)
	{
		//traiter des requêtes
		$workflow = $trans->fetchWorkflow();
		$status = ActesTransaction :: getStatusList();
		
		$this->pdf->SetFont('Arial','',12);
		$this->myRectangle(65);
		$this->pdf->Cell(40,10,"Cycle de vie de la transaction :",0,1);
		$this->pdf->SetMyWidths(array(10,50,60,60));
		$this->pdf->SetMyAligns(array('C','C','C','C'));
		$this->pdf->SetMyBorder(array('0','R','RL','L'));
		$this->pdf->SetFont('Arial','B',10);
		$this->pdf->setMyFillcolor(array(array(255,255,255),array(200,220,255),array(200,220,255),array(200,220,255)));
		$this->pdf->myRow(array("","Etat","Date", "Message"));
		$this->pdf->SetFont('Arial','i',10);
		$this->pdf->SetMyBorder(array('R','1','1','1'));
		$this->pdf->setMyFillcolor(array(array(255,255,255),array(216,252,254),array(216,252,254),array(216,252,254)));
		foreach ($workflow as $stage) 
		{
			$this->pdf->myRow(array("",$status[$stage["status_id"]],Helpers :: getDateFromBDDDate($stage["date"], true),$stage["message"]));
		}
	}
	
  /**
  * \brief ajouter un retangle sur un cell
  * \param $w, h=width , hight. position est défini par la position du cell.
  * 
  */	
	protected function 	myRectangle($w,$h=6)
	{
		$x=$this->pdf->GetX();
		$y=$this->pdf->GetY();
		$this->pdf->SetFillColor(192);
		
		// pour changer le style, voir le commentaire de la fonction rounderect ExtendPdf::RoundeRect()
		$this->pdf->RoundedRect($x-2, $y+2, $w, $h, 3, 'DF', '13');
	}
}