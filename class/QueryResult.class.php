<?php

/**
 * Class QueryResult
 */
class QueryResult {

	private $result_ressource;

	public function __construct($result_ressource) {
		$this->result_ressource=$result_ressource;
	}

	/** WTF... en simplifiant ca donne toujours false */
	public function isError() {
		return false;
	}

	// Compte les lignes de resultat
	public function num_row() {
		return @pg_num_rows($this->result_ressource);
	}

	// Compte les colonnes de resultat
	public function num_field() {
		return @pg_num_fields($this->result_ressource);
	}

	// Retourne la ligne courante resultat ou FALSE si plus de lignes
	public function get_next_row() {
		return @pg_fetch_assoc($this->result_ressource);
	}

	public function affected_row(){
		return @pg_affected_rows ($this->result_ressource);
	}

	public function get_all_rows() {
		$out=array();
		while ($row=@pg_fetch_assoc($this->result_ressource)) $out[]=$row;
		return $out;
	}
}

