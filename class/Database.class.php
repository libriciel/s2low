<?php
require_once __DIR__ ."/Trace.class.php";
require_once __DIR__."/DatabasePool.class.php";
require_once __DIR__."/QueryResult.class.php";

class Database {

	/**
	 * @var string machine supportant la base PostgresSQL
	 */
	private $host;

	/**
	 * @var string Utilisateur de la base
	 */
	private $user;

	/**
	 * @var string Mot de passe de connexion
	 */
	private $password;

	/**
	 * @var string Base de donnees courante
	 */
	private $base;

	/**
	 * @var null|resource Lien sur la base
	 */
	private $link = null;

	public $errorbox;

	public $display_warning;

	public $exit_on_error;

	/**
	 * @var bool true si une transaction est en cours
	 */
	public $transaction_mode;

	/**
	 * @var string contient le message de la première erreur
	 */
	public $transaction_error;

	public $query_log;

	public $last_query;
	public $last_query_error;

  	public function __construct($host=DB_HOST, $user=DB_USER, $password=DB_PASSWORD, $base=DB_DATABASE) {
		$this->host     = $host;
		$this->user    = $user;
		$this->password = $password;
		$this->base    = $base;

		if (MODE == "dev") {
		  $this->errorbox = true;
		  $this->display_warning=true;
		} else {
		  $this->errorbox = false;
		  $this->display_warning = false;
		}

		$this->exit_on_error=true;
		$this->transaction_mode=false;
		$this->transaction_error='';
		$this->query_log=false;
		$this->last_query="No request yet";
		$this->last_query_error="";
	}

  	public function connect() {
		if ($this->link){
			return true;
		}

		// Connexion à la base    host=sheep port=5432 dbname=marie user=mouton password=baaaa
		$connection_string= "dbname=".$this->base." user=".$this->user." password=".$this->password;
		if ($this->host != "") $connection_string="host=".$this->host." ".$connection_string;
		$this->link = pg_connect($connection_string);
		if ( ! $this->link  ) {
			header("Location: /maintenance.php");
			exit(false);
		}
		pg_set_client_encoding  ( DB_CLIENT_ENCODING );

		pg_query("SET standard_conforming_strings = off;");
		return true;
  	}

  	public function close() {
    	pg_close($this->link);
    	return true;
  	}

  	public function log($state=true) {
    	return $this->query_log=($state?true:false);
  	}

  	public function lastRequest() {
    	return $this->last_query;
  	}

  	public function lastRequestError() {
    	return $this->last_query_error;
  	}


	/**
	 * retourne les éléments dans la réponse
	 * @param $query
	 * @param string $key
	 * @param string $value
	 * @return array
	 * @throws Exception
	 */
	public function selectData($query,$key='',$value='') {
		$out=array();
		$q=$this->select($query);
		//$nb=$q->num_row();
		while ($r=$q->get_next_row()) {
		  if ($key=='') {
		$out[]=$r;
		  } else {
		if ($value=='')
		  $out[$r[$key]]=$r;
		else
		  $out[$r[$key]]=$r[$value];
		  }
		}
		$q->Free();
		return $out;
  	}

	/**
	 * renvoie une ressource sur QueryResult
	 * @param $query
	 * @param bool $return_queryresult NE JAMAIS PASSER LE SECOND PARAMETRE => USAGE INTERNE
	 * @return bool|QueryResult
	 * @throws Exception
	 */
  	public function select($query,$return_queryresult=true) {
		if (! $this->link) {
		  if (! $this->connect()) {
		return false;
		  }
		}
		//echo $query . "<br />\n";
		if ($this->query_log) echo "<br />".$query;
		$this->last_query=$query;
		//echo "query=$query";
		//echo "query=".$this->last_query;
		$this->last_query_error='';


		$trace = Trace::getInstance();
		$trace->log($query,Trace::$TRACE_DEBUG);

		// Execution de la requete
		$result = @pg_query($query);

		// Test du resultat
		if ($result == false) {
		  $error=pg_last_error();
		  $this->last_query_error=$error;
		  if ($this->display_warning)
			 echo "<br />Query: $query<br />".$error."<br />";

		  $trace->log("Erreur SQL :  " . $error,Trace::$TRACE_ERROR);


		  if ($this->transaction_mode && $this->transaction_error=='') {
			$this->transaction_error=$error;
		  }

		  if ($this->errorbox) {
		// Traitement de l'erreur
		$message =  "Erreur d'&eacute;xecution de requ&ecirc;te. <br />requete=".$query.".<br />erreur pg=".$error.".<br /><br />Contacter l'administrateur systeme.";
		$message.="<pre>".print_r(debug_backtrace(),true)."</pre>";
		//$err = new Message_err($message, "Erreur Base de Donn&eacute;es", ERR_ICO_ERR);
		  }

		  if ($this->exit_on_error) {
				  throw new Exception("ERREUR SQL");

		  }
		  return ($return_queryresult?new QueryResult($query,$result,$error):false);
		}
		$this->query=$result;
		return ($return_queryresult?new QueryResult($query,$result):true);
	}


	/**
	 * @param $query
	 * @return bool|QueryResult
	 * @throws Exception
	 */
	public function exec($query) {
		return $this->select($query,false);
	}

	public function begin() {
		if ($this->transaction_mode) {
		  // Déjà en mode transactionnel
		  if ($this->display_warning) echo "Database.class.php:begin(): ATTENTION, une transaction est déjà en cours\n";
		  return 0;
		}
		if ($this->exec("BEGIN")===true) {
		  $this->transaction_mode=true;
		  $this->transaction_error='';
		  return 1;
		}
		if ($this->display_warning) echo "Database.class.php:begin(): begin failed()\n";
		return 0;
  	}

	public function commit() {
		if (!$this->transaction_mode) {
		  // pas en mode transactionnel
		  if ($this->display_warning) echo "Database.class.php:commit(): ATTENTION, aucune transaction en cours pour commiter\n";
		  return 0;
		}
		if ($this->exec("COMMIT")===true) {
		  $this->transaction_mode=false;
		  if ($this->transaction_error=='') return 1;
		}
		if ($this->display_warning) echo "Database.class.php:commit(): commit failed\n";
		return 0;
  	}

	public function rollback() {
		if (!$this->transaction_mode) {
		  // pas en mode transactionnel
		  if ($this->display_warning) echo "Database.class.php:rollback(): ATTENTION, aucune transaction en cours pour rollbacker\n";
		  return 0;
		}
		if ($this->exec("ROLLBACK")===true) {
		  $this->transaction_mode=false;
		  //$this->transaction_error='';
		  return 1;
		}
		if ($this->display_warning) echo "Database.class.php:rollback(): rollback failed\n";
		return 0;
  	}

	public function transactionError() {
	    return $this->transaction_error;
  	}

	public function quote($valeur,$notnull=false) {
		if ($valeur!="") {
		  $valeur=str_replace("\r\n","\n",$valeur);
		  return "'".addslashes($valeur)."'";
		} else {
		  if ($notnull)
		return "''";
		  else
		return 'NULL';
		}
  	}

	public function getOneLine($sql){
  		$result = $this->select($sql);
		return $result->get_next_row();
	}
	
	public function getOneValue($sql){
		$result = $this->getOneLine($sql);
		if (!$result){
			return false;
		}
		
		foreach($result as $val){
			return $val;
		}
	}
	
	public function fetchAll($sql){
		$result = $this->select($sql);
		$tabResult = array();
		while ($ligne = $result->get_next_row()){
			$tabResult[] = $ligne;
		}
		return $tabResult;		
	}
}
