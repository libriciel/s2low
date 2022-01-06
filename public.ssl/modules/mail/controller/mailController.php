<?php

require_once ( dirname(__FILE__) . "/../om/MailPeer.class.php");
require_once ( dirname(__FILE__) . "/../om/mail_transaction.class.php");
require_once ( dirname(__FILE__) . "/../lib/MailList.class.php");

class mailController {

  private $MailMessageEmis=array ();
  private $MailAnnuaireArray=array();
  
  private $lastError;
	
  
  public function exitIfNotAdmin(){
	global $me;  	
  	if (! $me->isAuthorityAdmin()){
  		exit;
  	}
  }
  
  /**
   * \bref dispatch le message.
   * 
   * \param char $action
   * 		
   */
	public function run($action)
	{
		
		// action will be called in index.php,initialize by diffrent type of action.
		switch ($action)
		{
			case "create"	:
				$this->executeCreate();
				break;
			case "list" 	:
				$this->executeList();
				break;
			case "show"		:
				$this->executeShow();
				break;
			case "send" :
				$this->executeSendAndDisplayResult();
			   break;
			case "SaveError":
				$this->SaveError();
				break;
			case "annuaire":
				$this->exitIfNotAdmin();
				$this->executeAnnuaire();
				break;
			case "savenewemail":
				$this->executeSaveNewEmail();
				break;
			case "import_annuaire" : 
				$this->exitIfNotAdmin();
				$this->executeImportAnnuaire();
				break;
			default:
				$this->executeList();
		}
	}
	
/**
 * \bref list les email reçu.
 * \bref appelé just par mailctroller::run();
 * \param: pas de parametre
 */
	protected function executeList() {
     
     global $me;   
     global $doc;
     $etat = "";
     $sujet = "";
     $SendDateFrom = "";
     $SendDateTo = "";
     $search=Helpers :: getVarFromGet("search");
     $deleteId=Helpers :: getVarFromPost("list_id");

     //---delete l'enregistment choisi.
     //FIXME : ca n'a rien à foutre là: faire un script intermédiaire
     if ($deleteId!=null)
     {
     	foreach ($deleteId as $transId) {
			MailPeer::DeleteMailTransation($transId);
		}
     }
     //----delete fini
     
     //contruit la filtre sql requete.
  	 $MailTransaction=new mail_transaction();
  	 if (!$search) {
  	 	$MailTransactions= MailPeer::mailList($MailTransaction,$me->getId());
  	 } else {  	 	
  	 	$etat=Helpers :: getVarFromGet("etat");
  	 	
  	 	$tabStatus = mail_transaction::getTabStatus();
  	 	$etat_string = $tabStatus[$etat];

  	 	$sujet = utf8_decode(Helpers :: getVarFromGet("sujet"));
  	 	$SendDateFrom=Helpers :: getVarFromGet("SendDateFrom");
  	 	$SendDateTo=Helpers :: getVarFromGet("SendDateTo");
  	 	$cond=" user_id=".$me->getId();
  	 	
  	 	if ($etat_string)
  	 		$cond.=" and status='".$etat_string."'";
  	 	if ($sujet)
  	 		$cond.=" and objet ILIKE '%".addslashes($sujet)."%'";
  	 	if ($SendDateFrom)
  	 		$cond.=" and date_envoi >='".addslashes($SendDateFrom)."'";
  	 	if ($SendDateTo)
  	 		$cond.=" and date_envoi <='".addslashes($SendDateTo)."'";


  	 	$MailTransactions=MailPeer::mailSearch($MailTransaction,$cond);
  	 }
  	 
        $doc->buildPager($MailTransaction, true);
        $doc->closeSideBar(true);
        $doc->openContent(true);
	include __DIR__."/../template/list.php";	
  }

  /**
   * 
   * 
   * FIXME FIXME
   * 
   * passer par un script intermédiaire
   * 
   * FIXME FIXME
   * 
   * 
 * \bref créer un nouvel email.
 * \bref appelé just par mailctroller::run();
 * \param: pas de parametre
 */
  protected function executeCreate()
  {  	
   //traitement des information
    global $me;
    global $doc;
    global $module;
   	require_once (__DIR__."/../om/MailPeer.class.php");     
  	require_once (__DIR__."/../om/mail_annuaire.class.php");    
  
        //fini de la tratement
        //affichier la page
        $doc->closeSideBar(true);
        $doc->openContent(true);
        include (__DIR__."/../template/create.php");
  }
  
  /**
 * \bref afficher le détail d'un email.
 * \bref appelé juste par mailController::run();
 * \param: pas de paramètre
 */
  protected function executeShow()
  {
   require_once (__DIR__."/../om/MailPeer.class.php");
   require_once (__DIR__."/../om/mail_message_emis.class.php");
   require_once (__DIR__."/../om/mail_included_file.class.php");
   require_once (__DIR__."/../om/mail_errors.class.php");
   global $doc;
   $error=$this->SaveError();
   //traitement des information
      try{
          $trans_id=Helpers::getIntFromGet("trans_id");
      } catch (Exception $e) {
          echo $e->getMessage();
          return false;
      }
   $mailTransaction=new mail_transaction($trans_id);
   $mailTransaction->init();   
   $fndownload=$mailTransaction->getFNDownload();   
   
   $mailEmisArray=MailPeer::GetMailEmis($trans_id);
   //print_r($mailEmisArray);
   if ( $mailEmisArray == null)
   {
   		echo "Le mail n'existe pas.";
   		return false;
   }
   $mailErrors=MailPeer::GetMailErrors($trans_id);
   $mailIncludeFileArray=MailPeer::GetIncludeFiles($trans_id);
  //fini de la tratement
   //affichier la page
   $doc->closeSideBar(true);
   $doc->openContent(true);
   include __DIR__."/../template/show.php";
  }
  
	//HACK béquille pour transformer les mails ...
	public function explodeMail($mail){
		
		//BEURK....
		global $me;
		 
		$result = array();
		$lesMails = explode(",",$mail);
		foreach($lesMails as $un_mail){
			$un_mail = trim($un_mail);
			if (  ! $un_mail){
				continue;
			}
			$matches  = array();
			if (preg_match('/(.*) \(groupe\)/',$un_mail,$matches)){
				$groupe_name = $matches[1];
				$groupeMail = new GroupeMail();
				$groupe_id = $groupeMail->getGroupeIdFromName($groupe_name,$me->get('authority_id'));
				if ($groupe_id){				
					$annuaire = MailPeer::GetAnnuaire($me->get('authority_id'),$groupe_id);
					foreach ($annuaire as $personne){
						if ($personne['description']){
							$result[] = '"'.$personne['description'].'" <' .$personne['mail_address'] .'>';
						} else {
							$result[] = $personne['mail_address'];	
						}
					}
					
				}
			} else {
				$un_mail = str_replace("[","<",$un_mail);
				$un_mail = str_replace("]",">",$un_mail);
				$result[] = $un_mail;
			}			
		}	
		$newresult=array();
		foreach ($result as $chaine){
			if (!in_array($chaine, $newresult)){
				$newresult[]=$chaine;
			}
		}
		return implode(",",$newresult);
	}
  
	public function getLastError(){
		return $this->lastError;
	}
	
	
	public function executeSendAndDisplayResult(){
		global $doc;
		$result = $this->executeSend();
		
		if ( ! $result){
			$returnMsg = $this->getLastError(); ;
                        $doc->closeSideBar(true);
                        $doc->openContent(true);
			include __DIR__."/../template/sendfailed.php"; 
		} else {
                    $doc->closeSideBar(true);
                    $doc->openContent(true);
                    include __DIR__."/../template/send.php";	
                }
 		
	}   
	
	public function logError(){
		global $me, $module;
		$result = Log :: newEntry(LOG_ISSUER_NAME, $this->lastError , 3, false, 'USER', $module->get("name"), $me);
		if (! $result){
			$this->lastError .= "\nErreur de journalisation.";
		}  
	}
	
	
	public function executeSend() {
		//FIXME fonction trop grande ...
  	
		
	  	require_once (dirname(__FILE__)."/../om/mail_message_emis.class.php");
	  	require_once (dirname(__FILE__)."/../om/MailPeer.class.php");    
	  	require_once (dirname(__FILE__)."/../lib/mailfunction.php");      
	   	require_once (dirname(__FILE__)."/../lib/MailUtil.class.php");
	   	
	   	global $me, $module;
	   
		//HACK
		if (empty($_POST) && empty($_FILES)){
			$this->lastError = "Les pièces jointes sont trop volumineuses (80 Mo maximum)" ;
	       	return false;
		}

	   	//vérification de mail adress.
		$mailTo=Helpers :: getVarFromPost("mailto");
	    $mailCC=Helpers :: getVarFromPost("mailcc");   
	    $mailBCC=Helpers :: getVarFromPost("mailcci");
	   
	    
	    $mailTo = $this->explodeMail($mailTo);
	    $mailCC = $this->explodeMail($mailCC);
	    $mailBCC = $this->explodeMail($mailBCC);
	  
	    $subject=Helpers :: getVarFromPost("objet");
	    $message=Helpers :: getVarFromPost("message");
	    $message = str_replace("\r", "", $message);
	    $send_password = Helpers :: getVarFromPost("send_password");
	    
	    if ( ! $mailTo ) {
	    	$this->lastError = "Le destinataire est obligatoire";
	    	return false;
	    }
	    
    	if (checkAllEmail($mailTo)==false) {
			$this->lastError = "L'adresse email est incorrecte ! mailto=$mailTo ";
			return false;
		}

		if ( ! $subject ) {
    		$this->lastError = "L'objet du mail est obligatoire";
    		return false;
		}
		
		if ( ! $message ) {
	    	$this->lastError =  "Le corps du message ne peut pas être vide";
	    	return false;
		}

		if (strlen($message)>2000){
            $this->lastError =  "Le corps du message ne peut dépasser les 2000 caractères : ".strlen($message)." caractères trouvés.";
            return false;
        }
		if ($mailCC && ! checkAllEmail($mailCC)) {
    		$this->lastError =  "mailCC: Adresse email incorrecte !";
    		return false;
    	}
    	
    	if ($mailBCC && ! checkAllEmail($mailBCC)) {
    		$this->lastError =  "mailBCC: Adresse email incorrecte !";
    		return false;
	    }
    
		//-------fini de la vérification
	    //----ini mail tranaction.
	    // mail transaction faut absolutment inite avant tous les autre opération car tous les autre tableau need 
	    // mail transaction id.
	    $mailTransaction=new mail_transaction();
	    $mailTransaction->newSave($me->getId());
	    $Transaction_id=$mailTransaction->getId();
	    $mailIncludedFiles=array();

	    //--------------------------------------------------------------------  
	    //FileNumber = le nombre de File est attaché. Il commence par 1. 
	    //Il est défini dans le fichier de javascript file: mail.js
	    $InputFileName=array();
	    $FileNumber = Helpers :: getVarFromPost("FileNumber");
	    if ($FileNumber !=null)
	    {
	      
	       require_once (dirname(__FILE__)."/../om/mail_included_file.class.php");
	       for ($i = 1; $i <= $FileNumber; $i++)
	       {
	          // le nom de uploadFile pass par var _FILES
	          // le nom de chaque file =uploadFile1, uploadFile2,,,,jusqu'à FileNumber
	          // parcque des fois les utilisateur supprime une fichier qu'il a déjas ajouté et le FileNumber va pas diminuer enmeme temp
	          // donc il y aura de trou entre les nombre.
	        if (defined('MAIL_DEBUG'))
			 		{	
	        	echo "filenumber=".$i;  
	       		echo "filename=".$_FILES['uploadFile'. $i]['name'];
	 				}
	        if  ( !  empty($_FILES['uploadFile'. $i]['name']))
	        {
	        	$InputFileName[]='uploadFile'. $i;
	           
	        }
	      }
	   		}
   		 //----------------------
	
		//TODO : MAL
		global $myAuthority;

	    $mailHeader = ObjectInstancierFactory::getObjetInstancier()->get(MailHeader::class);

		$mailHeader->setAuthorityName($myAuthority->get('name'));
		$mailHeader->setFromMail($myAuthority->get('email_mail_securise'));
		$mailHeader->setFromDescription($myAuthority->get('descr_mail_securise'));

        $mailUtil = new MailUtil($mailHeader);
	
  	if (count($InputFileName)>0)
  	{
	    $now = date("Y-m-d H:i:s");
	    $mailTransaction->set("fn_download",md5("mail".$now).mt_rand(0,mt_getrandmax()));
	    $mailTransaction->save(false);
	    
	    require_once ( __DIR__."/../om/mail_included_file.class.php");
        
        // créer un repertoir de md5
  		$newdir=MAIL_FILES_UPLOAD_ROOT."/".$mailTransaction->getFNDownload().'/';
  		if (!mkdir ($newdir, 0755, true))
  		{
  			$this->lastError ="La création de répertoire a echoué.";
  			$this->logError();
  			return false;
  		}
  		$mailFiles=array();
	    foreach ($InputFileName as $Filename)
	    {
	      	 $temp=new mail_included_file();
             if ($temp->newSave($Filename,$Transaction_id,$newdir))
 		      	$mailIncludedFiles[]=$temp;
 		     else 
 		     {
	 		     	$this->lastError ="Le chargement du fichier sur le server a échoué : " . $temp->getLastError();
		 			$this->logError();
	 		     	return false;
			}
 	    }
	    foreach ($mailIncludedFiles as $mailIncludeFile)
	    {
	      $mailFiles[]=$newdir.$mailIncludeFile->getFileName();
	    }
	    $Zipfile=$newdir."mail.zip";
	    if (!$mailUtil->zip($mailFiles,$Zipfile))
	    {
	      $this->lastError = $mailUtil->errorMsg;
			$this->logError();
	      return false;
	    }
		foreach ($mailIncludedFiles as $mailIncludeFile)
		{
			unlink($newdir.$mailIncludeFile->getFileName());
		}
  	}
  	else 
  	{
  		$mailTransaction->set("fn_download",null);
  		$mailTransaction->save(false);
  	}
  	
  	$this->SaveMailEmis($mailTo,$Transaction_id,"mailTo");
	$this->SaveMailEmis($mailCC,$Transaction_id,"mailCC");
	
	$this->SaveMailEmis($mailBCC,$Transaction_id,"mailBCC");
	
	if (!$mailUtil->sendMail($this->MailMessageEmis,$mailTransaction,$mailIncludedFiles,$send_password))
	{	
	  	$this->lastError = "Échec lors de l'envoi.";
		$this->logError();
	  	//traiter les messages d'échec.
	  	$mailTransaction->delete();
	  	foreach ($this->MailMessageEmis as $mailEmis )
	  	{
	  		$mailEmis->delete();
	  	}
	  	foreach ($mailIncludedFiles as $file)
	  	{
	  		$file->delete();
	  	}
	  	return false;
	}
	$msg="Envoi de mail réussi.";
	if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me))
  {
    $this->lastError = "\nErreur de journalisation.";

    return false;
  }
  
   return $mailTransaction->getID();  
  }

/**
 * \bref envoyer ajouter ou supprimer un contact dans l'annuaire.appelé juste par mailController::run();
 *		
 * \param pas de paramètre
 */
  protected function executeAnnuaire() {
  	global $me;
        global $doc;
  	$email = Helpers :: getVarFromPost("email");



  	$description = Helpers :: getVarFromPost("description");
  	
  	$id = Helpers :: getVarFromPost("id");
  	
  	if ($email != null )  {
		if (! is_valid_email($email)){
			$_SESSION['last_message'] = "L'email n'est pas valide";
		} else {
			$annuaire = new mail_annuaire();
			$annuaire->set("mail_address", $email);
			$annuaire->set("description", $description);
			$annuaire->set("authority_id", $me->get('authority_id'));
			$annuaire->set("id", $id);
			$annuaire->save(false);
		}
  	}
  	$idArray= Helpers :: getVarFromPost("checkbox_id");
  	
  	$groupe_id = Helpers :: getVarFromPost("groupe_id");
  	$old_groupe_id = Helpers :: getVarFromPost("old_groupe_id");
  	$groupe = new GroupeMail($groupe_id);
  	
  	if ($idArray != null)
  	{
  		foreach ($idArray as $id) {
  			if ($groupe_id){
  				if ($old_groupe_id == $groupe_id) {
					$groupe->removeUser($id);
  				} else {
  					$groupe->addUser($id);
  				}
  			} else {
  			    if ($groupe->isUserInGroup($id)){
                    $this->lastError = "Impossible de supprimer un utilisateur qui est encore dans un groupe";

                } else {
                    $annuaire = new mail_annuaire($id);
                    $annuaire->delete();
                }
  			}
  		}
  		unset($groupe_id);
  		if ($this->lastError){
            $_SESSION['last_error'] = $this->lastError;
        } else {
            $_SESSION['last_message'] = "Opération effectuée avec succés";
        }
  	}
  	
  	if ($old_groupe_id) {
  		$groupe_id = $old_groupe_id;
  	} else {
          try{
              $groupe_id = Helpers :: getIntFromGet("groupe_id",true);
          } catch (Exception $e){
              echo $e->getMessage();
              return false;
          }
  	}
  	
	$mailAnnuaireArray=MailPeer::GetAnnuaire($me->get('authority_id'),$groupe_id);
	$groupe = new GroupeMail();
	$groupeArray = $groupe->getGroupeByAuthorityId($me->get('authority_id'));
	$bd = DatabasePool::getInstance();
	$annuaire = new Annuaire($bd,$me->get('authority_id'));

    if(!is_null($groupe_id) && !in_array($groupe_id,array_keys($groupeArray))){
        $_SESSION['last_error'] = "Le group_id '$groupe_id' n'existe pas.";
        $groupe_id = null;
    }

	if ($groupe_id ){
		foreach($groupeArray as $groupe){
			if ($groupe['id'] == $groupe_id) {
				$groupe_name = $groupe['name'];
				break;		
			}
		}
	}
                $doc->closeSideBar(true);
                $doc->openContent(true);
		include __DIR__."/../template/annuaire.php";	 
  }
  
/**
 * \bref: examiner la boit au lettre de tedetis,
 * \bref		récupérer les nouveau email
 * \bref		trouver le quelle mail n'est pas réussi d'envoyeer
 * \bref		sauvegarder dans la base de donnee
 * \bref	appelé just par mailctroller::show();
 * \param: pas de parametre
 */
  protected function SaveError()
  {
  	  require_once (MAIL_SITEROOT."/lib/MailUtil.class.php");
  	  require_once (MAIL_SITEROOT."/om/mail_errors.class.php");
  	  $mailUtil = new MailUtil();
  	  $mailMessageArray=$mailUtil->GetMailMessage();
  	  if ($mailMessageArray == null)
  	  {
  	  	//echo "mail emis failed.";
  	  	return false;
  	  }
	  $length=sizeof($mailMessageArray["mail_emis_id"]);
  	  for($i=0;$i<$length;$i++)
  	  {
  	  	  $mailErros=new mail_errors();
	  	  $mailErros->set("mail_message_emis_id",$mailMessageArray["mail_emis_id"][$i]);
	  	  $mailErros->set("message_retour",$mailMessageArray["body"][$i]);
	  	  $now = date("Y-m-d H:i:s");
	  	  $mailErros->set("date_registered",$now);
	  	  $mailErros->save(false);
	  }
  	  return true;
  }
 
/**
 * \bref save les mail emis dans tableau mail_emis
 * \bref	appelé just par mailctroller::run();
 * \param: pas de parametre
 */
  protected function SaveMailEmis($mail, $Transaction_id,$type)
  {
	if ($mail==null)
		return false;
  	 global $me;
  	 //supprime le vircule a la fin.
  	 // le séparateur  is vircule
   	 $Emails=explode(",",$mail);  	
  	 foreach ($Emails as $Email)
     {	
     	
     	
       	$Email = str_replace ("[","<", $Email); 
		$Email = str_replace("]",">",$Email);
		$Email = trim($Email);
		
     	if ($Email!="")
     	{
        	$this->MailMessageEmis[]=new mail_message_emis();
       		if ( end($this->MailMessageEmis)->newSave($Email,$Transaction_id,$type)==false) {
       			
       			return false;
       		}
       			
        //vérifier le mail adress exist déjas ou pas
        //si non; met dans MailAnnuaireArray pour traiter aprés.
        	if (MailPeer::VerifierMailAnnuaire($Email,$me->get('authority_id'))==false)
        		$this->MailAnnuaireArray[]=$Email;
     	}
     }
  return true;
  }
 
  protected function executeSaveNewEmail()
  {
  	require_once ( __DIR__."/../om/mail_annuaire.class.php");
  	global $me;
  	global $doc;
  	$emails = Helpers :: getVarFromPost("newMailAddress");
  	$descriptions = Helpers :: getVarFromPost("newMailDescription");
  	$maxLengh=count($emails);
  	
  	echo $maxLengh;
  	for ($i=0;$i<$maxLengh;$i++) {
  		$annuaire=new mail_annuaire();
  		$annuaire->set("user_id",$me->getId());
  		$annuaire->set("mail_address",$emails[$i]);
  		$annuaire->set("description",$descriptions[$i]);
  		$annuaire->save(false);
  	}
        $doc->closeSideBar(true);
        $doc->openContent(true);
  	include __DIR__."/../template/newemail.php";	 
  }


}


