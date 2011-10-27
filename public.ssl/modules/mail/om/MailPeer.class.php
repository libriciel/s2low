<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à la
 * dématèrialisation de l'administration. 
 *
 * Ce logiciel est régi par la licence CeCILL soumise au droit français et
 * respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusée par le CEA, le CNRS et l'INRIA 
 * sur le site "http://www.cecill.info".
 *
 * En contrepartie de l'accessibilité au code source et des droits de copie,
 * de modification et de redistribution accordés par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitée.  Pour les mêmes raisons,
 * seule une responsabilité restreinte pèse sur l'auteur du programme,  le
 * titulaire des droits patrimoniaux et les concédants successifs.
 *
 * A cet égard  l'attention de l'utilisateur est attirée sur les risques
 * associés au chargement,  à l'utilisation,  à la modification et/ou au
 * développement et à la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à 
 * manipuler et qui le réserve donc à des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à charger  et  tester  l'adéquation  du
 * logiciel à leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * à l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder à cet en-tête signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/
/**
 * \class MailPeer  MailPeer.class.php
 * \brief Cette classe permet de traiter entre les tableaux
 * 
 * \author TH ,JMontiel
 * \date :23-04-2008
 * 
 *
 * Cette classe fournit des méthodes de traiter les requete de base de donner et les objets de tableaux.
 * Modifications :
 * Auteur   Date       Commentaire
 * PEV		15/08/2010	Avec Postgres 8.4 les requêtes impliquant la table mail_error posait des problèmes. Les requêtes avaient pour condiction un char = un int.
 */

require_once (MAIL_SITEROOT."/om/mail_transaction.class.php");
require_once("mail_included_file.class.php");


class MailPeer {

  public static function mailList(&$MailTransaction,$userId) { 
  	$Field="id,objet, status,date_envoi ";
  	$from="mail_transaction";
  	$cond="where user_id=".$userId;
  	if ($MailTransaction->pagerInit($Field,$from,$cond))
  		return $MailTransaction->data;
  	else 
  		return false;
  }
  
   public static function mailSearch(&$MailTransaction,$cond)
  { 	
  	$Field="DISTINCT id,objet,status,date_envoi ";
	$from="mail_transaction ";
  	$cond="where ".$cond;
	if ($MailTransaction->pagerInit($Field,$from,$cond))
  		return $MailTransaction->data;
  	else 
  		return false;  	
  }
  
  //FIXME fonction catastrophique...
	public static function GetMailEmis($trans_id) {
  		
		assert('$trans_id');
		
		$db =& DatabasePool::getInstance();
		
		$MailEmisArray = array();

	  	$sql = "SELECT id FROM mail_message_emis " .
	  			" WHERE mail_transaction_id = $trans_id" .
	  			" ORDER BY email";
		
	  	$result = $db->select($sql);
		   
		    if (! $result->isError()) 
		    {
			    while ($row = $result->get_next_row()) 
			    {
			    	//FIXME : ICI : on fait une requete par mail
			      $obj = new mail_message_emis($row["id"]);  
			      if ($obj->init()) 
			      {
			        $MailEmisArray[] = $obj;
			      }
		      	}
	     	}
	     	return $MailEmisArray;
  }
  
  public static function GetIncludeFiles($trans_id)
  {
  		
  	 $MailIncludeFileArray = array();
	  if (! empty($trans_id)) 
	  	{
			$sql = "SELECT id FROM mail_included_file where";
		    $sql.= " mail_transaction_id =".$trans_id;
			$db = DatabasePool::getInstance();
		    $result = $db->select($sql);
		    if (! $result->isError()) 
		    {
			    while ($row = $result->get_next_row()) 
			    {
			      $obj = new mail_included_file($row["id"]);
			      if ($obj->init()) 
			      {
			        $MailIncludeFileArray[] = $obj;
			      }
		      	}
	     	}
	     	return $MailIncludeFileArray;
     	} 
     return false;
  }
  
  
	public static function GetAnnuaire($authority_id,$groupe_id = null) {
		assert('$authority_id');
		$sql = "SELECT * FROM mail_annuaire ";
		if ($groupe_id){
			$sql .= " JOIN mail_user_groupe ON mail_annuaire.id = mail_user_groupe.id_user ";
		}
		$sql .= " WHERE mail_annuaire.authority_id=$authority_id ";
		
		if ($groupe_id){
			$sql .= " AND mail_user_groupe.id_groupe = $groupe_id";
		}
		
		$sql.= " ORDER BY COALESCE(description,mail_address) ;";
		
		$db =& DatabasePool::getInstance();
		$result = $db->select($sql);		
		return $result->get_all_rows();
	}
  
  
  /**
   * \bref:recuperer les email adress et message retour par 2 tableau:
   * mail_errors, mail_message_emis
   * pour un email spécifier.
   *
   * @param $trans_id=>mail_trainsaction id:
   * @return un tableau: 2 colone: mail adress et message retour
   */
  public static function GetMailErrors($trans_id)
  {
	  if (! empty($trans_id)) 
	  	{
			$sql = "SELECT mail_message_emis.email, mail_errors.message_retour FROM mail_errors, mail_message_emis where";
		    $sql.= " mail_errors.id = mail_message_emis.mail_transaction_id and mail_message_emis.mail_transaction_id=".$trans_id;
			$db =& DatabasePool::getInstance();
		    $result = $db->select($sql);
			return $result->get_all_rows();
     	} 
     return false;
  }
  
  /**
   * \bref: vérifier une email adress est déjas dans la tableau de annuaire
   * 
   * @param string $mail :adress qu'on va vérifier
   * @param integer $userId: pour quelle user
   * @return if exist, return true, si non, return false.
   */
  public static function VerifierMailAnnuaire($mail,$authority_id)
  {
  	  if (!empty($mail))
  	  {
  	  	$db =& DatabasePool::getInstance();
  	  	
  	  	$sql="SELECT id FROM mail_annuaire WHERE ";
  	  	$sql.=" mail_address=".$db->quote($mail)." and authority_id=".$authority_id;
  	  			$result = $db->select($sql);
		$idArray=$result->get_all_rows();
		if (count($idArray)>0)
			return true;
  	  }
  	  else
  	  	return false;
  }
  
  /**
   * \bref supprimer l'enregistment(n-uplet) correspond de trans mail id 
   * \ aussi les relation sur les autre tableau ;
   * \ : mail_message_emis
   * \ : mail_included_file
   * \ : mail_errors
   * @param integer $transId
   */
  public static function DeleteMailTransation($transId)
  {
  	$message=array();
  	$db =& DatabasePool::getInstance();
  	
  	$sql="DELETE FROM mail_errors WHERE id IN ";
  	$sql.="(SELECT mail_message_emis.mail_transaction_id FROM mail_message_emis WHERE mail_transaction_id=$transId)";		
    if (! $db->exec($sql)) {
  		$message[]= "Erreur lors de la suppression de mail_errors ";

    }
  	
  	$sql="delete FROM mail_message_emis WHERE mail_transaction_id=".$transId;

    if (! $db->exec($sql)) {
  		$message[]="Erreur lors de la suppression de mail_transaction_id ";
  		
    }	
	
  	$sql="delete FROM mail_included_file WHERE mail_transaction_id=".$transId;	
    if (! $db->exec($sql)) {
  		$message[]="Erreur lors de la suppression de mail_included_file ";
  		
    }	

  	$sql="delete FROM mail_transaction WHERE id=".$transId;		
    if (! $db->exec($sql)) {
  		$message[]="Erreur lors de la suppression de mail_transaction";
    }	
	return $message;
  }
}
  