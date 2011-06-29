<?php 

class TransactionSQL {
	
	const EN_COURS = 10;
	
	private static $transactionTypes = array (
	    "1" => "Transmission d'actes",
	  	"2" => "Courrier simple",
	  	"3" => "Demande de pièces complémentaires",
	  	"4" => "Lettre d'observation",
	  	"5" => "Déféré au Tribunal Administratif",
	    "6" => "Annulation",
	    "7" => "Demande de classification"
	);

	private static $natureTransaction = array (
		3 => 'Arretes individuels',
		2 => 'Arretes reglementaires', 
		6 => 'Autres',
		4 => 'Contrats et conventions',
		1 => 'Deliberations',
		5 => 'Documents budgetaires et financiers' 
	);
	
	private static $status = array ( 
		-1 => 'Erreur',
		0 => 'Annulé',
		1 => 'Posté',
		2=> 'En attente de transmission',
		3 => 'Transmis', 
		4 => 'Acquittement reçu', 
		5 => 'Validé',
		6 => 'Refusé',
		7 => 'Document reçu',
		8 => 'Acquittement envoyé',
		9 => 'Document envoyé',
		10 => "Refus d'envoie",
		11 => 'Aquittement de document reçu'
	); 
	
	
	private $sqlQuery;
	private $filter;
	private $value;
	private $offset;
	private $limit;
	private $order;
	private $sortWay;
	
	public function __construct($sqlQuery){		
		$this->sqlQuery = $sqlQuery;
		$this->limit = 10;
		$this->offset = 0;
		$this->filter = array();
		$this->value = array();
	}
	
	public function getTypes(){
		return self::$transactionTypes;
	}
	
	public function getNatures(){
		return self::$natureTransaction;
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
		
		$this->order = ($order=='id')?'actes_transactions.id':'submission_date';
		$this->sortWay = ($sortway=='asc')?'ASC':'DESC';
	}
	
	public function setAuthority($authority_id){
		if (! $authority_id){
			return ;
		}
		$this->filter[] .= "actes_transactions.authority_id=?";
		$this->value[] =$authority_id;
	}
	
	public function setUserId(array $user_id){
		$this->filter[] = "actes_transactions.user_id IN (".implode(',',$user_id).")";
	}
	
	public function setNature($nature){
		if (! $nature){
			return;
		}
		$this->filter[] = "actes_transactions.nature_code = ?";
		$this->value[] = $nature;
	}
	
	public function setType($type) {
		if (! $type){
			return;
		}
		$this->filter[] = "actes_transactions.type=?";
		$this->value[] = $type;
	}
	
	public function setStatus($status){
		if ($status === 'all'){
			return;
		}
		if ($status == self::EN_COURS){
			 $this->filter[] = "actes_transactions.last_status_id  IN (1, 2, 3, 4)";
			
		} else {
			$this->filter[] .= "actes_transactions.last_status_id  = ?" ;
			$this->value[] = $status;
		}
	}
	
	
	public function setNumero($numero){
		if (! $numero){
			return;
		}
		$this->filter[] = "actes_transactions.number LIKE ?";
		$this->value[] = "%$numero%";
	}
	
	public function setObjet($objet){
		if (! $objet){
			return;
		}
		$this->filter[] = "actes_transactions.subject LIKE ?";
		$this->value[] = "%$objet%";
	}
	
	
	public function setDateMinSubmission($date){
		if (! $date)  {
			return;
		};
		$this->filter[] = "(SELECT date " .
						" FROM actes_transactions_workflow atw " .
						" WHERE actes_transactions.id = atw.transaction_id " .
						" AND ( atw.status_id = 1 OR atw.status_id = 7 ) LIMIT 1 ) >= ? ";
		$this->value[] = $date;
	}
	
	public function setDateMaxSubmission($date){
		if (! $date) return;
		$this->filter[] = "(SELECT date " .
						" FROM actes_transactions_workflow atw " .
						" WHERE actes_transactions.id = atw.transaction_id " .
						" AND ( atw.status_id = 1 OR atw.status_id = 7 ) LIMIT 1 ) <= ? ";
		$this->value[] = $date;
	}
	
	public function setDateMinAck($date){
		if (! $date) return;
		$this->filter[] = "(SELECT date " .
						" FROM actes_transactions_workflow atw " .
						" WHERE actes_transactions.id = atw.transaction_id " .
						" AND atw.status_id =  4 LIMIT 1 ) >= ? ";
		$this->value[] = $date;
	}
	
	public function setDateMaxAck($date){
		if (! $date) return;
		$this->filter[] = "(SELECT date " .
						" FROM actes_transactions_workflow atw " .
						" WHERE actes_transactions.id = atw.transaction_id " .
						" AND atw.status_id =  4 LIMIT 1 ) <= ? ";
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
				" envelope_id,  " .
				" submission_date, "  .
				" users.name,".
				" users.givenname," .
				" authorities.name as authority_name, " .
				" actes_transactions.id as transaction_id, " .
				" type,number,subject,archive_url,nature_descr,actes_transactions.last_status_id " .
				" FROM actes_transactions " .
				" JOIN actes_envelopes ON actes_transactions.envelope_id = actes_envelopes.id " .
				" JOIN users ON actes_envelopes.user_id=users.id " .
				" JOIN authorities ON users.authority_id=authorities.id " .
				$this->getWhere() .		
				" ORDER BY $this->order $this->sortWay " .
				" LIMIT $this->limit OFFSET $this->offset";
		$result = $this->sqlQuery->query($sql,$this->value);
		foreach($result as $i => $line){
			$result[$i]['type_str'] = self::$transactionTypes[$line['type']];
			$result[$i]['current_status'] = $line['last_status_id'];
			$result[$i]['current_status_name'] = self::$status[$line['last_status_id']];
			$result[$i]['courrier_info'] = $this->getCourrierInfo($line['transaction_id']);
		}
		return $result;
	}

	public function getNbTransaction(){
		$where = "";
		if ($this->filter) {
		  $where = "WHERE " . implode($this->filter, " AND ");
		}
		$sql = "SELECT count(id)   " .
				" FROM actes_transactions  " .
				// " JOIN  actes_envelopes ON actes_envelopes.id=actes_transactions.envelope_id ".
				$this->getWhere() ;
		return $this->sqlQuery->queryOne($sql,$this->value);
	}
	
	public function getCourrierInfo($transaction_id){
	  	$result = array();
	  	$sql = " SELECT at1.id as id, at2.id as related_transaction_id,at1.type as type ".
	  			" FROM actes_transactions at1 " .
	  			" LEFT JOIN actes_transactions at2 ON at1.id=at2.related_transaction_id  ". 
	  			" WHERE at1.related_transaction_id=? AND (at2.type != '6' OR at2.type IS NULL) ";
	  	
	  	foreach($this->sqlQuery->query($sql,$transaction_id) as $line) {
	  		
	  		$rline = array("type" => $line["type"],"type_str" => self::$transactionTypes[$line['type']]);
	  		
			if (! $line["related_transaction_id"]) {  		
				$id = 	$line["id"];
				$rline['sens'] = "reçu";
			} else {
				$id = 	$line["related_transaction_id"];
				$line['sens'] = "envoye";
			}
			$result[$id] = $rline;
	  	}
		return $result;
	}
	
	
	public function delete($id){
		$sql = "DELETE FROM actes_included_files WHERE transaction_id=?";
		$this->sqlQuery->query($sql,$id);
		$sql  = "DELETE FROM actes_transactions_workflow WHERE transaction_id=?";
		$this->sqlQuery->query($sql,$id); 
		$sql = "DELETE FROM actes_transactions WHERE id=?";
		$this->sqlQuery->query($sql,$id);
	}
	
}