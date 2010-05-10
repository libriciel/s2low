<?php
/*
* TéDéTIS - Copyright 2006 Alternance-Soft
* Contributeur : Jérôme Schell, AoÃ»t 2006
*
* contact@alternancesoft.com
*
* Ce logiciel est un programme informatique servant Ã  la
* dÃ©matÃ©rialisation de l'administration.
*
* Ce logiciel est rÃ©gi par la licence CeCILL soumise au droit franÃ§ais et
* respectant les principes de diffusion des logiciels libres. Vous pouvez
* utiliser, modifier et/ou redistribuer ce programme sous les conditions
* de la licence CeCILL telle que diffusÃ©e par le CEA, le CNRS et l'INRIA
* sur le site "http://www.cecill.info".
*
* En contrepartie de l'accessibilitÃ© au code source et des droits de copie,
* de modification et de redistribution accordÃ©s par cette licence, il n'est
* offert aux utilisateurs qu'une garantie limitÃ©e.  Pour les mÃªmes raisons,
* seule une responsabilitÃ© restreinte pÃ¨se sur l'auteur du programme,  le
* titulaire des droits patrimoniaux et les concÃ©dants successifs.
*
* A cet Ã©gard  l'attention de l'utilisateur est attirÃ©e sur les risques
* associÃ©s au chargement,  Ã  l'utilisation,  Ã  la modification et/ou au
* dÃ©veloppement et Ã  la reproduction du logiciel par l'utilisateur Ã©tant
* donnÃ© sa spÃ©cificitÃ© de logiciel libre, qui peut le rendre complexe Ã  
* manipuler et qui le rÃ©serve donc Ã  des dÃ©veloppeurs et des professionnels
* avertis possÃ©dant  des  connaissances  informatiques approfondies.  Les
* utilisateurs sont donc invitÃ©s Ã  charger  et  tester  l'adÃ©quation  du
* logiciel Ã  leurs besoins dans des conditions permettant d'assurer la
* sÃ©curitÃ© de leurs systÃ¨mes et ou de leurs donnÃ©es et, plus gÃ©nÃ©ralement,
* Ã l'utiliser et l'exploiter dans les mÃªmes conditions de sÃ©curitÃ©.
*
* Le fait que vous puissiez accÃ©der Ã  cet en-tÃªte signifie que vous avez
* pris connaissance de la licence CeCILL, et que vous en avez acceptÃ© les
* termes.
*/
?>
<?php
/**
* \class DatabasePool Database.class.php
* \brief Classe de gestion d'un pool d'accès vers des bases de données
* \author Jérôme Schell <j.schell@alternancesoft.com>
* \date 18.07.2006
*
*
* Cette classe gère un pool d'accès vers des bases de données.
* Comportement type "singleton" lors de la récupération d'une instance.
*
* Modifications :
* Auteur   Date       Commentaire
*
*/

require_once(SITEROOT ."/class/Trace.class.php");

class DatabasePool {
  /**
   * retourne une référence vers un objet de type Database correspondant
   * aux paramètres de connexion spécifiés lors de l'appel
   *
   * @param $host string le nom du serveur de base de données
   * @param $user string nom d'utilisateur pour la connexion à la base
   * @param $password mot de passe pour la connexion à la base
   * @param $base nom de la base de données à laquelle se connecter
   * @return une référence vers un objet de type Database permettant de faire des requêtes sur la base choisie
   *
   */
  function &getInstance($host=DB_HOST, $user=DB_USER, $password=DB_PASSWORD, $base=DB_DATABASE) {
    static $pool = array();

    // On cherche si l'on a une instance correspondant aux paramètres spécifiés
    $found = false;
    while (list($key, $conn) = each($pool) && ! $found) {
      if ($conn->host == $host && $conn->user == $user && $conn->password == $password && $conn->base == $base) {
    $found = true;
    $goodKey = $key;
      }
    }

    // On n'a pas trouvé d'instance => soit le pool est vide soit il ne contient pas la connexion désirée
    if (! $found) {
      $goodKey = count($pool);
      $pool[$goodKey] = new Database($host, $user, $password, $base);
    }

    return $pool[$goodKey];
  }
}

/**
* \class Database Database.class.php
* \brief classe de gestion et de requêtage d'une base de données.
* \author EP
* \author OR
* \author Jérôme Schell <j.schell@alternancesoft.com>
* \date 18.08.2004
*
*
* Cette classe fourni des méthodes d'encapsulation des appels à
* la base de données.
*
* Modifications :
* Auteur   Date       Commentaire
*
*/
class Database {
  //****************************
  // Donnees membres
  //****************************
  
  var $host;                // machine supportant la base PostgresSQL
  var $user;                // Utilisateur de la base
  var $password;            // Mot de passe de connexion
  var $base;                // Base de donnees courante
  var $link = null;                // Lien sur la base

  var $errorbox;
  var $display_warning;
  var $exit_on_error;

  var $transaction_mode; // true si une transaction est en cours
  var $transaction_error; // contient le message de la première erreur

  var $query_log;

  var $last_query;
  var $last_query_error;

  
  // Constructeur
  function Database($host=DB_HOST, $user=DB_USER, $password=DB_PASSWORD, $base=DB_DATABASE) {
    
    // Ecriture des donnees membres        
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

  function connect() {
    // Connexion à la base    host=sheep port=5432 dbname=marie user=mouton password=baaaa    
    $connection_string= "dbname=".$this->base." user=".$this->user." password=".$this->password;
    if ($this->host != "") $connection_string="host=".$this->host." ".$connection_string;
    $this->link = pg_connect($connection_string);
    if ($this->link == FALSE) {
      //Traitement de l'erreur
      //$error=pg_last_error(); echo $error;
      header("Location: /maintenance.php");
      exit(false);
    }
     pg_set_client_encoding  ( "LATIN9" );
    
    return true;
  }
  
  // Méthode de fermeture de la session base de données
  function close() {
    pg_close($this->link);
    return true;
  }

  function log($state=true) {
    return $this->query_log=($state?true:false);
  }

  function lastRequest() {
    return $this->last_query;
  }

  function lastRequestError() {
    return $this->last_query_error;
  }

  // retourne les éléments dans la réponse
  function selectData($query,$key='',$value='') {
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
  
  // renvoie une ressource sur QueryResult
  // NE JAMAIS PASSER LE SECOND PARAMETRE => USAGE INTERNE
  function select($query,$return_queryresult=true) {
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
      if ($this->display_warning) echo "<br />Query: $query<br />".$error."<br />";

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

  // renvoie true ou false
  function exec($query) {
    return $this->select($query,false);
  }

  function begin() {
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

  function commit() {
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

  function rollback() {
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

  function transactionError() {
//     if (!$this->transaction_mode) {
//       // pas en mode transactionnel
//       if ($this->display_warning) echo "Database.class.php:transaction_error(): ATTENTION, aucune transaction en cours\n";
//       return false;
//     }
    return $this->transaction_error;
  }

  function quote($valeur,$notnull=false) {
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
	
	function fetchAll($sql){
		$result = $this->select($sql);
		$tabResult = array();
		while ($ligne = $result->get_next_row()){
			$tabResult[] = $ligne;
		}
		return $tabResult;		
	}
}

/**
* \class QueryResult Database.class.php
* \brief Classe encapsulant un résultat de requête vers une base de données
* \author OR
* \date 18.08.2004
*
*
* Cette classe fourni des méthodes d'encapsulation des accès
* à un objet résultat de base de données.
*
* Modifications :
* Auteur   Date       Commentaire
*
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

function debugdbok() {
  if (MODE == "dev") {
    global $DB;
    echo "<br />".htmlentities($DB->LastRequest())."<br />";
  }
}

function debugdbko() {
  if (MODE == "dev") {
    global $DB;
    echo "<br />".htmlentities($DB->LastRequest())."<br />".htmlentities($DB->LastRequestError())."<br />";;
  }
}

?>
