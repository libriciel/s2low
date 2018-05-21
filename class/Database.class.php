<?php
require_once __DIR__ ."/Trace.class.php";
require_once __DIR__."/DatabasePool.class.php";
require_once __DIR__."/QueryResult.class.php";

class Database {

	private $database_host;
	private $database_user;
	private $database_password;
	private $database_name;

	private $connection_link = null;
	private $is_in_a_transaction;


	/**
	 * @var string contient le message de la première erreur
	 */
	private $has_transaction_error;

  	public function __construct($host=DB_HOST, $user=DB_USER, $password=DB_PASSWORD, $base=DB_DATABASE) {
		$this->database_host     = $host;
		$this->database_user    = $user;
		$this->database_password = $password;
		$this->database_name    = $base;
		$this->is_in_a_transaction=false;
		$this->has_transaction_error=false;
	}

  	public function connect() {
		if ($this->connection_link){
			return true;
		}

		// Connexion à la base    host=sheep port=5432 dbname=marie user=mouton password=baaaa
		$connection_string= "dbname=".$this->database_name." user=".$this->database_user." password=".$this->database_password;
		if ($this->database_host != "") $connection_string="host=".$this->database_host." ".$connection_string;
		$this->connection_link = pg_connect($connection_string);
		if ( ! $this->connection_link  ) {
			header("Location: /maintenance.php");
			exit(false);
		}
		pg_set_client_encoding  ( DB_CLIENT_ENCODING );

		pg_query("SET standard_conforming_strings = off;");
		return true;
  	}

	/**
	 * renvoie une ressource sur QueryResult
	 * @param $query
	 * @return QueryResult
	 * @throws Exception
	 */
  	public function select($query) {
		$result = $this->internalQuery($query);
		return new QueryResult($result);
	}

	/**
	 * @param $query
	 * @return bool
	 * @throws Exception
	 */
	public function exec($query) {
		$this->internalQuery($query);
		return true;
	}

	/**
	 * @param $query
	 * @return bool|resource
	 * @throws Exception
	 */
	private function internalQuery($query){
		if (! $this->connection_link) {
			if (! $this->connect()) {
				return false;
			}
		}

		$trace = Trace::getInstance();
		$trace->log($query,Trace::$TRACE_DEBUG);

		// Execution de la requete
		$result = @pg_query($query);

		// Test du resultat
		if ($result == false) {
			$error=pg_last_error();

			$trace->log("Erreur SQL :  " . $error,Trace::$TRACE_ERROR);

			if ($this->is_in_a_transaction) {
				$this->has_transaction_error = true;
			}

			$error = strval(utf8_decode(pg_last_error()));
			throw new Exception($error);
		}
		return $result;
	}


	/**
	 * @return int
	 * @throws Exception
	 */
	public function begin() {
		if ($this->is_in_a_transaction) {
		  	return 0;
		}
		if ($this->exec("BEGIN") === true) {
		  	$this->is_in_a_transaction = true;
		  	$this->has_transaction_error = false;
		  	return 1;
		}
		return 0;
  	}

	/**
	 * @return int
	 * @throws Exception
	 */
	public function commit() {
		if (!$this->is_in_a_transaction) {
		  	return 0;
		}
		if ($this->exec("COMMIT") === true) {
		  	$this->is_in_a_transaction=false;
		  	if (! $this->has_transaction_error) {
		  		return 1;
			}
		}
		return 0;
  	}

	/**
	 * @return int
	 * @throws Exception
	 */
	public function rollback() {
		if (!$this->is_in_a_transaction) {
		  	return 0;
		}
		if ($this->exec("ROLLBACK")===true) {
		  	$this->is_in_a_transaction = false;
		  	return 1;
		}
		return 0;
  	}

	public function quote($valeur,$notnull=false) {
		if ($valeur!="") {
			  $valeur=str_replace("\r\n","\n",$valeur);
			  return "'".addslashes($valeur)."'";
		} else {
		  	if ($notnull) {
				return "''";
			}  else {
				return 'NULL';
			}
		}
  	}

	/**
	 * @param $sql
	 * @return array
	 * @throws Exception
	 */
	public function getOneLine($sql){
  		$result = $this->select($sql);
		return $result->get_next_row();
	}

	/**
	 * @param $sql
	 * @return bool|mixed
	 * @throws Exception
	 */
	public function getOneValue($sql){
		$result = $this->getOneLine($sql);
		if (!$result){
			return false;
		}
		return reset($result);
	}

	/**
	 * @param $sql
	 * @return array
	 * @throws Exception
	 */
	public function fetchAll($sql){
		$result = $this->select($sql);
		$tabResult = array();
		while ($ligne = $result->get_next_row()){
			$tabResult[] = $ligne;
		}
		return $tabResult;		
	}
}
