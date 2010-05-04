<?php

require_once (dirname(__FILENAME__)."/om/MailPeer.class.php");
require_once (dirname(__FILENAME__)."/om/mail_transaction.class.php");

class mailController {
	
  private $MailMessageEmis=array ();
  private $MailAnnuaireArray=array();
	
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
				$this->executeSend();
			   break;
			case "SaveError":
				$this->SaveError();
				break;
			case "annuaire":
				$this->executeAnnuaire();
				break;
			case "savenewemail":
				$this->executeSaveNewEmail();
				break;
			case "import_annuaire" : 
				$this->executeImportAnnuaire();
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
     $search=Helpers :: getVarFromPost("search");
     $deleteId=Helpers :: getVarFromPost("list_id");
     
     //---delete l'enregistment choisi.
     //FIXME : ca n'a rien à foutre là: faire un script intermédiaire
     if ($deleteId!=null)
     {
     	foreach ($deleteId as $transId)
     		$deleteMessage=MailPeer::DeleteMailTransation($transId);
     }
     //----delete fini
     
     //contruit la filtre sql requete.
  	 $MailTransaction=new mail_transaction();
  	 if (!$search) {
  	 	$MailTransactions= MailPeer::mailList($MailTransaction,$me->getId());
  	 } else {  	 	
  	 	$etat=Helpers :: getVarFromPost("etat");
  	 	
  	 	$tabStatus = mail_transaction::getTabStatus();
  	 	$etat_string = $tabStatus[$etat];
  	 	
  	 	$sujet=Helpers :: getVarFromPost("sujet");
  	 	$SendDateFrom=Helpers :: getVarFromPost("SendDateFrom");
  	 	$SendDateTo=Helpers :: getVarFromPost("SendDateTo");
  	 	$cond=" user_id=".$me->getId();
  	 	
  	 	if ($etat_string)
  	 		$cond.=" and status='".$etat_string."'";
  	 	if ($sujet)
  	 		$cond.=" and objet ILIKE '%".$sujet."%'";
  	 	if ($SendDateFrom)
  	 		$cond.=" and date_envoi >='".addslashes($SendDateFrom)."'";
  	 	if ($SendDateTo)
  	 		$cond.=" and date_envoi <='".addslashes($SendDateTo)."'";
  	 	$MailTransactions=MailPeer::mailSearch($MailTransaction,$cond);
  	 }
  	 
   	 $doc->buildPager($MailTransaction,true);
	include dirname(__FILENAME__)."/template/list.php";	
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
    global $module;
   	require_once (dirname(__FILENAME__)."/om/MailPeer.class.php");     
  	require_once (dirname(__FILENAME__)."/om/mail_annuaire.class.php");    
  
   //fini de la tratement
   //affichier la page
   include dirname(__FILENAME__)."/template/create.php";
  }
  
  /**
 * \bref afficher le détail d'un email.
 * \bref appelé juste par mailController::run();
 * \param: pas de paramètre
 */
  protected function executeShow()
  {
   require_once (dirname(__FILENAME__)."/om/MailPeer.class.php");
   require_once (dirname(__FILENAME__)."/om/mail_message_emis.class.php");
   require_once (dirname(__FILENAME__)."/om/mail_included_file.class.php");
   require_once (dirname(__FILENAME__)."/om/mail_errors.class.php");
   
   $error=$this->SaveError();
   //traitement des information
   $trans_id=Helpers::getVarFromGet("trans_id");    
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
   include dirname(__FILENAME__)."/template/show.php";
  }
  
	//HACK béquille pour transformer les mails ...
	private function explodeMail($mail){
		
		//BEURK....
		global $me;
		 
		$result = array();
		$lesMails = explode(",",$mail);
		foreach($lesMails as $un_mail){
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
		return implode(",",$result);
	}
  
/**
 * \bref envoyer un email.
 * \bref appelé juste par mailController::run();
 * \param: pas de paramètre
 */
  protected function executeSend()
  {
		//FIXME fonction trop grande ...
  	
  	
  	require_once (dirname(__FILENAME__)."/om/mail_message_emis.class.php");
  	require_once (dirname(__FILENAME__)."/om/MailPeer.class.php");    
  	require_once (dirname(__FILENAME__)."/lib/mailfunction.php");      
   	require_once (dirname(__FILENAME__)."/lib/MailUtil.class.php");
   	
   	global $me, $module;
   	
   	//vérification de mail adress.
	$mailTo=Helpers :: getVarFromPost("mailto");
    $mailCC=Helpers :: getVarFromPost("mailcc");
    $mailBCC=Helpers :: getVarFromPost("mailbcc");
    
    $mailTo = $this->explodeMail($mailTo);
    $mailCC = $this->explodeMail($mailCC);
    $mailBCC = $this->explodeMail($mailBCC);
  
    $subject=Helpers :: getVarFromPost("objet");
    $message=Helpers :: getVarFromPost("message");
    $send_password = Helpers :: getVarFromPost("send_password");
    if ($mailTo==null)
    {
    	$returnMsg="Vous êtes obligé d'avoir un destinataire.";
    	include dirname(__FILENAME__)."/template/sendfailed.php";	
    	return false;
    }
    else
    {
    	if (checkAllEmail($mailTo)==false)
    	{
    		$returnMsg= "mailTo: Adresse email incorrecte !\n mailto=$mailTo ";
    		include dirname(__FILENAME__)."/template/sendfailed.php";	
    		return false;
    	}
    }
    if ($subject==null)
    {
    	$returnMsg= "Vous êtes obligé d'avoir un objet.";
      include dirname(__FILENAME__)."/template/sendfailed.php";	
    	return false;
    }
    if ($message==null)
    {
    	$returnMsg= "Le champ message doit être rempli";
    	include dirname(__FILENAME__)."/template/sendfailed.php";	
    	return false;
    }
    if ($mailCC)
    {
    	if (checkAllEmail($mailCC)==false)
    	{
    		$returnMsg= "mailCC: Adresse email incorrecte !";
    		include dirname(__FILENAME__)."/template/sendfailed.php";	
    		return false;
    	}
    	
    }
    if ($mailBCC)
    {
    	if (checkAllEmail($mailBCC)==false)
    	{
    		$returnMsg="mailBCC: Adresse email incorrecte !";
    		include dirname(__FILENAME__)."/template/sendfailed.php";	
    		return false;
    	}
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
      
       require_once (dirname(__FILENAME__)."/om/mail_included_file.class.php");
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
        if  ( $_FILES['uploadFile'. $i]['name'] != null)
        {
        	$InputFileName[]='uploadFile'. $i;
           
        }
      }
   }
    //----------------------


	$mailUtil = new MailUtil();
	
	//TODO : MAL
	global $myAuthority;
	
	$mailUtil->setSubjet("[".$myAuthority->get('name')."] ".MAIL_MESSAGE);
	
	if ($myAuthority->get('email_mail_securise')){
		$mailUtil->setFrom($myAuthority->get('email_mail_securise'));
	}
	
  	if (count($InputFileName)>0)
  	{
	    $now = date("Y-m-d H:i:s");
	    $mailTransaction->set("fn_download",md5("mail".$now));
	    $mailTransaction->save(false);
	    
	    require_once (dirname(__FILENAME__)."/om/mail_included_file.class.php");
        
        // créer un repertoir de md5
  		$newdir=MAIL_FILES_UPLOAD_ROOT.$mailTransaction->getFNDownload().'/';
  		if (!mkdir ($newdir, 0755, true))
  		{
  			$returnMsg="La création de répertoire a echoué.";
  			if (!Log :: newEntry(LOG_ISSUER_NAME, $returnMsg, 3, false, 'USER', $module->get("name"), $me))
			  {
			    $returnMsg= "\nErreur de journalisation.";
			    include dirname(__FILENAME__)."/template/sendfailed.php";	
			    return false;
			  }  
  			include dirname(__FILENAME__)."/template/sendfailed.php";	
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
	 		     	$returnMsg="Le chargement du fichier sur le server a échoué : " . $temp->getLastError();
		 		    if (!Log :: newEntry(LOG_ISSUER_NAME, $returnMsg, 3, false, 'USER', $module->get("name"), $me))
					  {
					    $returnMsg= "\nErreur de journalisation.";
					    include dirname(__FILENAME__)."/template/sendfailed.php";	
					    return false;
					  }
	 		     	include dirname(__FILENAME__)."/template/sendfailed.php";	
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
	      $returnMsg= $mailUtil->errorMsg;
				if (!Log :: newEntry(LOG_ISSUER_NAME, $returnMsg, 3, false, 'USER', $module->get("name"), $me))
			  {
			    $returnMsg= "\nErreur de journalisation.";
			    include dirname(__FILENAME__)."/template/sendfailed.php";	
			    return false;
			  }
	      include dirname(__FILENAME__)."/template/sendfailed.php";	
	      return false;
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
	  	$returnMsg= "Échec lors de l'envoi.";
			if (!Log :: newEntry(LOG_ISSUER_NAME, $returnMsg, 3, false, 'USER', $module->get("name"), $me))
		  {
		    $returnMsg= "\nErreur de journalisation.";
		    include dirname(__FILENAME__)."/template/sendfailed.php";	
		    return false;
		  }
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
	  	include dirname(__FILENAME__)."/template/sendfailed.php";	
	  	//-----
	  	return false;
	}
	$msg="Envoi de mail réussi.";
	if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me))
  {
    $returnMsg= "\nErreur de journalisation.";
    include dirname(__FILENAME__)."/template/sendfailed.php";	
    return false;
  }
   // traitement fini
   //afficher la page
   include dirname(__FILENAME__)."/template/send.php";	
   return true;  
  }

/**
 * \bref envoyer ajouter ou supprimer un contact dans l'annuaire.appelé juste par mailController::run();
 *		
 * \param pas de paramètre
 */
  protected function executeAnnuaire()
  {
  	require_once (dirname(__FILENAME__)."/om/mail_annuaire.class.php");
  	require_once (dirname(__FILENAME__)."/om/MailPeer.class.php");
  	global $me;
  	$email = Helpers :: getVarFromPost("email");
  
  	$description = Helpers :: getVarFromPost("description");
  	
  	if ($email != null)
  	{
  		$annuaire=new mail_annuaire();
  		$annuaire->set("mail_address",$email);
  		$annuaire->set("description",$description);
  		$annuaire->set("authority_id",$me->get('authority_id'));
  		$annuaire->save(false);
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
  				$annuaire=new mail_annuaire($id);
  				$annuaire->delete();
  			}
  		}
  		unset($groupe_id);
  		$_SESSION['last_message'] = "Opération effectué avec succès";
  	}
  	
  	if ($old_groupe_id) {
  		$groupe_id = $old_groupe_id;
  	} else {
		$groupe_id = Helpers :: getVarFromGet("groupe_id");
  	}
  	
	$mailAnnuaireArray=MailPeer::GetAnnuaire($me->get('authority_id'),$groupe_id);
	$groupe = new GroupeMail();
	$groupeArray = $groupe->getGroupeByAuthorityId($me->get('authority_id'));
	$bd = DatabasePool::getInstance();
	$annuaire = new Annuaire($bd,$me->get('authority_id'));
	
	if ($groupe_id){
		foreach($groupeArray as $groupe){
			if ($groupe['id'] == $groupe_id) {
				$groupe_name = $groupe['name'];
				break;		
			}
		}
	}
	
	
	
  	include dirname(__FILENAME__)."/template/annuaire.php";	 
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
        //si non; met dans MailAnnuaireArray pour traiter apès.
        	if (MailPeer::VerifierMailAnnuaire($Email,$me->get('authority_id'))==false)
        		$this->MailAnnuaireArray[]=$Email;
     	}
     }
  return true;
  }
 
  protected function executeSaveNewEmail()
  {
  	require_once (dirname(__FILENAME__)."/om/mail_annuaire.class.php");
  	global $me;
  	$emails = Helpers :: getVarFromPost("newMailAddress");
  	$descriptions = Helpers :: getVarFromPost("newMailDescription");
  	$maxLengh=count($emails);
  	
  	echo $maxLengh;
  	//return;
  	for ($i=0;$i<$maxLengh;$i++)
  	{
  		$annuaire=new mail_annuaire();
  		$annuaire->set("user_id",$me->getId());
  		$annuaire->set("mail_address",$emails[$i]);
  		$annuaire->set("description",$descriptions[$i]);
  		$annuaire->save(false);
  	}
  	//-----------------------------------
  	include dirname(__FILENAME__)."/template/newemail.php";	 
  }


}


