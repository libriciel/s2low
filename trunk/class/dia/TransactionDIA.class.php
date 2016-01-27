<?php 
class TransactionDIA {
	
	private $sqlQuery;
	private $filter;
	private $value;
	private $offset;
	private $limit;
	private $order;
	private $sortWay;
	
	const RECU = 1;
	const RECUPERE = 2 ;
	const AE_ENVOYE = 4;
	const ERREUR = 5;
	const ANP_RECU = 6;
	const ANP_ENVOYE = 7;
	
	
	
	private static $status = array ( 
		-1 => 'Erreur',
		1 => 'Reçu sur S²low',
		4 => 'AE envoyé vers PRESTO',
		2 => 'Récupéré',		
		3 => 'code abandonné',
		5 => 'Erreur sur la DIA',
		6 => 'Accusé de non préemption recu sur S²low',
		7 => 'Accusé de non préemption envoyé sur PEC',
	); 
	
	public function __construct($sqlQuery){		
		$this->sqlQuery = $sqlQuery;
		$this->limit = 10;
		$this->offset = 0;
		$this->filter = array();
		$this->value = array();
		$this->setOrder('id', 'desc');
	}
	
	public static function getStatusName($status_id){
		return self::$status[$status_id];
	}
	
	public function getStatus(){
		return self::$status;
	}
	
	public function setPageNumber($page_number,$taille_page){
		$this->offset = ($page_number - 1) * $taille_page;
		if ($this->offset < 0 ){
			$this->offset = 0;
		} 
		$this->limit = $taille_page;
		if ( ! $this->limit ){
			$this->limit = 10;
		}
	}
	
	public function setOrder($order,$sortway){
		$this->order = ($order=='id')?'dia_transactions.id':'submission_date';
		$this->sortWay = ($sortway=='asc')?'ASC':'DESC';
	}
	
	public function setAuthority($authority_id){
		if (! $authority_id){
			return ;
		}
		$this->filter[] .= " users.authority_id=? ";
		$this->value[] =$authority_id;
	}
	
	public function setUserId(array $user_id){
		$this->filter[] = "dia_transactions.user_id IN (".implode(',',$user_id).")";
	}
	
	public function setStatus($status){
		if ($status){
			$this->filter[] .= "dia_transactions.last_status_id  = ?" ;
			$this->value[] = $status;
		} 
	}
	
	public function setFilename($filename){
		if (! $filename){
			return;
		}
		$this->filter[] = "dia_transactions.filename ILIKE ?";
		$this->value[] = "%$filename%";
	}
	
	
	public function setDateMinSubmission($date){
		if (! $date)  {
			return;
		};
		$this->filter[] = "dia_transactions.submission_date >= ?";
		$this->value[] = $date;
	}
	
	public function setDateMaxSubmission($date){
		if (! $date) return;
		$this->filter[] = "dia_transactions.submission_date <= ?";
		$this->value[] = $date;
	}
	
	private function getWhere(){
		if (! $this->filter){
			return "";
		}
		return "WHERE " . implode($this->filter, " AND ");
	}
	
	public function getAll(){
		$sql = "SELECT " .
				" submission_date, "  .
				" users.name,".
				" users.givenname," .
				" authorities.name as authority_name, " .
				" dia_transactions.id as transaction_id, " .
				" dia_transactions.last_status_id, " .
				" dia_transactions.filename, " .
				" dia_transactions.file_size " .
				" FROM dia_transactions " .
				" JOIN users ON dia_transactions.user_id=users.id " .
				" JOIN authorities ON users.authority_id=authorities.id " .
				$this->getWhere() .		
				" ORDER BY $this->order $this->sortWay " .
				" LIMIT $this->limit OFFSET $this->offset";
	
		$result = $this->sqlQuery->query($sql,$this->value);
		foreach($result as $i => $line){
			$result[$i]['current_status'] = $line['last_status_id'];
			$result[$i]['current_status_name'] = self::$status[$line['last_status_id']];
		}
		return $result;
	}
	
	public function getAllId(){
		$sql = "SELECT " .
				" dia_transactions.id as transaction_id " .
				" FROM dia_transactions " .
				" JOIN users ON dia_transactions.user_id=users.id " .
				" JOIN authorities ON users.authority_id=authorities.id " .
				$this->getWhere();		
	
		$result = array();
		foreach($this->sqlQuery->query($sql,$this->value) as $i => $line){
			$result[] = $line['transaction_id'];
		}
		
		return $result;
	}
	

	public function getNbTransaction(){
		$where = "";
		if ($this->filter) {
		  $where = "WHERE " . implode($this->filter, " AND ");
		}
		$sql = "SELECT count(dia_transactions.id)   " .
				" FROM dia_transactions  " .
				" JOIN users ON dia_transactions.user_id=users.id " .
				$this->getWhere() ;
		return $this->sqlQuery->queryOne($sql,$this->value);
	}
	
	public function delete($id){
		$sql = "DELETE FROM dia_transactions_workflow WHERE transaction_id=?";
		$this->sqlQuery->query($sql,$id);
		$sql = "DELETE FROM dia_transactions WHERE id=?";
		$this->sqlQuery->query($sql,$id);
	}
	
	
	public function createDIA($user_id,$filename,$filesize,$message_id,$message_xml){
		$sql = "INSERT INTO dia_transactions ". 
				"(user_id,filename,file_size,submission_date,last_status_id, accuse_enregistrement,message_id,message_xml)" .
				" VALUES (?,?,?,now(),?,'',?,?)";
		$this->sqlQuery->query($sql,$user_id,$filename,$filesize,1,$message_id,$message_xml);
		$sql = "SELECT id FROM dia_transactions WHERE user_id=? AND filename=? AND file_size=? ORDER BY submission_date DESC LIMIT 1";
		$id = $this->sqlQuery->queryOne($sql,$user_id,$filename,$filesize);
		$this->updateStatus($id, self::RECU, "Récupération de la DIA");
		return $id;
	}
	
	public function messageExists($message_id){
		$sql = "SELECT * FROM dia_transactions WHERE message_id=?";
		return $this->sqlQuery->queryOne($sql,$message_id);
	}
	
	public function getInfo($id){
		$sql = "SELECT * FROM dia_transactions WHERE id=?";
		return $this->sqlQuery->queryOne($sql,$id);
	}
	
	public function getWorkflow($id){
		$sql = "SELECT * FROM dia_transactions_workflow WHERE transaction_id=? ORDER by date";
		return $this->sqlQuery->query($sql,$id);
	}
	
	public function updateStatus($id,$status_id,$message){
		$sql = "INSERT INTO dia_transactions_workflow(transaction_id,status_id,date,message) VALUES (?,?,now(),?)";
		$this->sqlQuery->query($sql,$id,$status_id,$message);
		$sql = "UPDATE dia_transactions SET last_status_id=? WHERE id=?";
		$this->sqlQuery->query($sql,$status_id,$id);
	}
	
	public function addAE($id,$filename){
		$sql = "UPDATE dia_transactions SET accuse_enregistrement=? WHERE id=?";
		$this->sqlQuery->query($sql,$filename,$id);
		$this->updateStatus($id, self::AE_ENVOYE, "Accusé d'enregistrement envoyé sur PEC");
	}
		
	public function addANP($id,$filename){
		$sql = "UPDATE dia_transactions SET accuse_non_preemption=? WHERE id=?";
		$this->sqlQuery->query($sql,$filename,$id);
		$this->updateStatus($id, self::ANP_RECU, "Accusé de non préemption reçu par S²low");
	}
	
	public function getStatusInfo($id,$status_id){
		$sql = "SELECT * FROM dia_transactions_workflow WHERE transaction_id=? AND status_id=?";
		return $this->sqlQuery->queryOne($sql,$id,$status_id);
	}
	
	public function getNeedAE(){
		$sql = "SELECT * FROM dia_transactions WHERE last_status_id=? or last_status_id=?";
		$result = array();
		foreach($this->sqlQuery->query($sql,self::RECU,self::RECUPERE) as $line){
			if ($this->getStatusInfo($line['id'],self::AE_ENVOYE)){
				continue;
			}
			$result[] = $line;
		}
		return $result;
	}
	
	public function getNeedSendANP(){
		$sql = "SELECT * FROM dia_transactions WHERE last_status_id=?";
		return $this->sqlQuery->query($sql,self::ANP_RECU);
	}
	
}