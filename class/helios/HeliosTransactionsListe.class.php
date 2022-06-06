<?php
class HeliosTransactionsListe {
	
	const EN_COURS = 999;
	
	private static $etat_en_cours = array(1,2,3,13,14);
	
	private $sqlQuery;
	private $filter;
	private $value;
	private $offset;
	private $limit;
	private $order;
	private $sortWay;
	
	
	public function __construct(SQLQuery $sqlQuery){
		$this->sqlQuery = $sqlQuery;
		$this->limit = 10;
		$this->offset = 0;
		$this->filter = array();
		$this->order = 'helios_transactions.id';
		$this->sortWay = 'DESC';
		$this->value = array();
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
		$this->order = ($order=='id')?'helios_transactions.id':'submission_date';
		$this->sortWay = ($sortway=='asc')?'ASC':'DESC';
	}
	
	public function setDateMinSubmission($date){
		if (! $date)  {
			return;
		}
		$this->filter[] = "(SELECT date " .
				" FROM helios_transactions_workflow atw " .
				" WHERE helios_transactions.id = atw.transaction_id " .
				" AND atw.status_id = 1  LIMIT 1 ) >= ? ";
		$this->value[] = $date;
	}
	
	public function setDateMaxSubmission($date){
		if (! $date) return;
		$this->filter[] = "(SELECT date " .
				" FROM helios_transactions_workflow atw " .
				" WHERE helios_transactions.id = atw.transaction_id " .
				" AND  atw.status_id = 1  LIMIT 1 ) <= ? ";
		$this->value[] = $date;
	}
	
	public function setDateMinAck($date){
		if (! $date) return;
		$this->filter[] = "(SELECT date " .
				" FROM helios_transactions_workflow atw " .
				" WHERE helios_transactions.id = atw.transaction_id " .
				" AND (atw.status_id =  4 OR atw.status_id= 6 OR atw.status_id=8 ) LIMIT 1 ) >= ? ";
		$this->value[] = $date;
	}
	
	public function setDateMaxAck($date){
		if (! $date) return;
		$this->filter[] = "(SELECT date " .
				" FROM helios_transactions_workflow atw " .
				" WHERE helios_transactions.id = atw.transaction_id " .
				" AND (atw.status_id =  4 OR atw.status_id= 6 OR atw.status_id=8) LIMIT 1 ) <= ? ";
		$this->value[] = $date;
	}
	
	public function setStatus($status){
		if ($status === 'all'){
			return;
		}
		if ($status == self::EN_COURS){
			$this->filter[] = "helios_transactions.last_status_id  IN (".implode(',',self::$etat_en_cours).")";
				
		} else {
			$this->filter[] .= "helios_transactions.last_status_id  = ?" ;
			$this->value[] = $status;
		}
	}
	
	public function setAuthority($authority_id){
		if (! $authority_id){
			return ;
		}
		$this->filter[] .= "helios_transactions.authority_id=?";
		$this->value[] =$authority_id;
	}
	
	public function setUserId(array $user_id){
		$this->filter[] = "helios_transactions.user_id IN (".implode(',',$user_id).")";
	}
	
	public function setObjet($objet){
		if (! $objet){
			return;
		}
		$this->filter[] = "helios_transactions.filename ILIKE ?";
		$this->value[] = "%$objet%";
	}

	public function setNomFic($fnomFic){
		if (! $fnomFic){
			return;
		}
		$this->filter[] = "helios_transactions.xml_nomfic ILIKE ?";
		$this->value[] = "%$fnomFic%";
	}


	private function getWhere(){
		if (! $this->filter){
			return "";
		}
		return "WHERE " . implode(" AND ", $this->filter);
	}
	
	public function getNbTransaction(){
		$where = "";
		if ($this->filter) {
			$where = "WHERE " . implode(" AND ", $this->filter);
		}
		$sql = "SELECT count(id)   " .
				" FROM helios_transactions  " .
		$this->getWhere() ;
		return $this->sqlQuery->queryOne($sql,$this->value);
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getAll(){
		$sql = "SELECT helios_transactions.id,  helios_transactions.last_status_id, helios_transactions.user_id, helios_transactions.xml_nomfic, helios_transactions.filename, submission_date,".
				" authorities.name as authority_name, " .
				" users.name,".
				" users.givenname " .
				" FROM helios_transactions " .
				" JOIN users ON helios_transactions.user_id=users.id " .
				" JOIN authorities ON helios_transactions.authority_id=authorities.id " .
				" WHERE helios_transactions.id IN ( SELECT id FROM helios_transactions ".
					$this->getWhere().
					" ORDER BY $this->order $this->sortWay " .
					" LIMIT $this->limit OFFSET $this->offset )".
			" ORDER BY $this->order $this->sortWay " ;

		$result = $this->sqlQuery->query($sql,$this->value);

		$sql = "SELECT message FROM helios_transactions_workflow WHERE transaction_id=? AND status_id=? ORDER BY id DESC limit 1";
		foreach($result as $i => $line){
			$result[$i]['message'] = $this->sqlQuery->queryOne($sql,$line['id'],$line['last_status_id']);
		}
		return $result;
	}
	
	
}