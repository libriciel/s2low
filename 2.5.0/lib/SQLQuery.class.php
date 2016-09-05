<?php
class SQLQuery {

	const DATABASE_TYPE = "pgsql";
	const DEFAULT_HOST = "localhost";
	const SLOW_QUERY_IN_MS = 2000;

	const CLIENT_ENCODING_DEFAULT = "LATIN9";

	private $databaseName;
	private $host;
	private $login;
	private $password;
	private $slow_query_in_ms;
	private $pdo;

	private $client_encoding;

	public function __construct($databaseName){
		$this->databaseName = $databaseName;
		$this->setDatabaseHost(self::DEFAULT_HOST);
		$this->setSlowQuery(self::SLOW_QUERY_IN_MS);
		$this->setClientEncoding(self::CLIENT_ENCODING_DEFAULT);
	}

	public function disconnect(){
		$this->pdo = null;
	}

	public function sleep($time_in_second){
		$this->disconnect();
		sleep($time_in_second);
	}

	public function setDatabaseHost($host){
		$this->host = $host;
	}

	public function setCredential($login,$password){
		$this->login = $login;
		$this->password = $password;
	}

	public function setSlowQuery($millisecond){
		$this->slow_query_in_ms  = $millisecond;
	}

	public function setClientEncoding($client_encoding){
		$this->client_encoding = $client_encoding;
	}

	public function getPdo(){
		if ( ! $this->pdo){
			$dsn = self::DATABASE_TYPE . ":host=".$this->host;
			if ($this->databaseName){
				$dsn .= ";dbname=".$this->databaseName;
			}
			$this->pdo = new PDO($dsn,$this->login,$this->password);
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$this->query("SET CLIENT_ENCODING TO '{$this->client_encoding}';");
		}
		return $this->pdo;
	}

	public function query($query,$param = false){
		$start = microtime(true);
		if ( ! is_array($param)){
			$param = func_get_args();
			array_shift($param);
		}

		try {
			$pdoStatement = $this->getPdo()->prepare($query);
		} catch (Exception $e) {
			throw new Exception($e->getMessage() . " - " .$query);
		}

		try {
			$pdoStatement->execute($param);
		} catch (Exception $e) {
			throw new Exception( $e->getMessage() ." - ". $pdoStatement->queryString . "|" .implode(",",$param));
		}
		$result = array();
		if ($pdoStatement->columnCount()){
			$result = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
		}

		$duration = microtime(true) - $start;
		if ($duration > $this->slow_query_in_ms ){
			$requete =  $pdoStatement->queryString . "|" .implode(",",$param);
			trigger_error("Requete lente ({$duration}ms): $requete",E_USER_WARNING);
		} // @codeCoverageIgnore

		return $result;
	}

	public function queryOne($query,$param = false){
		if ( ! is_array($param)){
			$param = func_get_args();
			array_shift($param);
		}
		$result = $this->query($query,$param);
		if (! $result){
			return false;
		}

		$result = $result[0];
		if (count($result) == 1){
			return reset($result);
		}
		return $result;
	}

	public function queryOneCol($query,$param = false){
		if ( ! is_array($param)){
			$param = func_get_args();
			array_shift($param);
		}
		$result = $this->query($query,$param);
		if (! $result){
			return array();
		}
		$r = array();
		foreach($result as $line){
			$line = array_values($line);
			$r[] = $line[0];
		}
		return $r;
	}

	/** @var  PDOStatement */
	private $lastPdoStatement;
	private $nextResult;
	private $hasMoreResult;

	public function prepareAndExecute($query,$param = false){
		if ( ! is_array($param)){
			$param = func_get_args();
			array_shift($param);
		}
		$this->lastPdoStatement = $this->getPdo()->prepare($query);
		$this->lastPdoStatement->execute($param);
		$this->hasMoreResult = true;
		$this->fetch();
	}

	public function hasMoreResult(){
		return $this->hasMoreResult;
	}

	public function fetch(){
		$result = $this->nextResult;
		$this->nextResult = $this->lastPdoStatement->fetch(PDO::FETCH_ASSOC,PDO::FETCH_ORI_NEXT);

		if (! $this->nextResult){
			$this->hasMoreResult = false;
		}
		return $result;
	}


}