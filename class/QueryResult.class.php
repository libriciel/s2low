<?php

/**
 * Class QueryResult
 */
class QueryResult {
	var $query;
	var $res;
	var $error;

	function QueryResult($query,$res,$error='') {
		$this->query=$query;
		$this->res=$res;
		$this->error=$error;
	}

	function free() {
		return @pg_free_result($this->res);
		$this->res=NULL;
	}

	function isError() {
		return ($this->error==''?false:true);
	}

	function error() {
		return $this->error;
	}

	// Compte les lignes de resultat
	function num_row() {
		$this->nb_row = @pg_num_rows($this->res);
		return $this->nb_row;
	}

	// Compte les colonnes de resultat
	function num_field() {
		return @pg_num_fields($this->res);
	}

	// Retourne la ligne courante resultat ou FALSE si plus de lignes
	function get_next_row() {
		return @pg_fetch_assoc($this->res);
	}

	function affected_row(){
		return @pg_affected_rows ($this->res);
	}

	function get_all_rows() {
		$out=array();
		while ($row=@pg_fetch_assoc($this->res)) $out[]=$row;
		return $out;
	}
}

