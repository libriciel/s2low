<?php 
require_once("Mail/RFC822.php");
require_once("PEAR.php");

//HACK béquille pour transformer les mails ...
	 function explodeMail($mail){
		
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
  function checkEmail($email,$antispam = false)
{

	if (!$email || !preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+([\.][a-z0-9-]+)+$/i",$email)){	
		return false;
	}
	
	if ($antispam) {		
		$email = str_replace("@", " at ", $email);		
		$email = str_replace(".", " dot ", $email);		
		return $email;		
	} else {	
		return true;	
	}
} 
function checkAllEmail($emailtext)
{	
 //	$emailtext=substr($emailtext,0,-1);
   // le séparateur  is vircule
    $mails=explode(",",$emailtext);  	
    foreach ($mails as $mail)
    {
     	$mail = str_replace ("[","<", $mail); 
		$mail = str_replace("]",">",$mail);		
    	if ($mail!=null)
    	
    		if (isValidMail($mail)==false)
    			return false;
    }
    return true;
}

	
 function isValidMail($mail){
		$lo_mail = Mail_RFC822::parseAddressList($mail, NULL, FALSE);
		if(PEAR::isError($lo_mail)){    
    		return false;
		} elseif ($lo_mail[0]->host=='localhost'){
			return false;
		}
		return true;
}


echo checkAllEmail(explodeMail("<pierrevuver@adullact.org>,"));
echo "\n";



