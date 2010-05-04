<?php 

//Les classes MailPeer et mail_transaction ne sont pas utilisable pour gérer l'API... (EP)

class MailList {
	
	private $db;
	private $id_user;
	
	public function __construct(Database $db,$id_user){
		$this->db = $db;
		$this->id_user = $id_user;
	}
	
	public function getNb(){
		return $this->db->getOneValue("SELECT count(*) FROM mail_transaction WHERE user_id=".$this->id_user);
	}
	
	public function getListMail($offset = 0 ,$limit = 20){

		if (! $offset){
			$offset = 0;
		}
		if (! $limit){
			$limit = 20;
		}
		
		$sql = "SELECT * FROM mail_transaction WHERE  user_id=".$this->id_user." ORDER BY date_envoi DESC LIMIT $limit OFFSET  $offset";
		return $this->db->fetchAll($sql);
	}
	
	public function getDetail($id_mail_transaction){
		$result= $this->db->getOneLine("SELECT * FROM mail_transaction WHERE id=$id_mail_transaction AND user_id=".$this->id_user);
		if (! $result){
			return false;
		}
		$sql = "SELECT * FROM mail_message_emis WHERE mail_transaction_id=$id_mail_transaction";
		$result['mail_emis'] =  $this->db->fetchAll($sql);
		
		$sql = "SELECT * FROM mail_included_file WHERE mail_transaction_id=$id_mail_transaction";
		
		$result['file'] = $this->db->fetchAll($sql);
		return $result;
	}
	
}