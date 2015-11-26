<?php


abstract class S2lowTestCase extends PHPUnit_Extensions_Database_TestCase {

	/**
	 * @var SQLQuery
	 */
	private static $sqlQueryStatic;

	private $objectInstancier;

	protected $backupGlobalsBlacklist = array('sqlQuery');

	protected function setUp(){
		parent::setUp();

		$this->getSQLQuery()->query("SELECT SETVAL('users_id_seq', (SELECT MAX(id)+1 FROM users))");
		$_GET = array();
		$_POST = array();
		$_SESSION = array();
		$_SERVER['SSL_CLIENT_VERIFY'] = "";
		$_SERVER['SSL_CLIENT_S_DN'] = "";
		$_SERVER['SSL_CLIENT_I_DN'] = "";
		$_SERVER['SSL_CLIENT_CERT'] = "";
		$_SERVER["QUERY_STRING"] = "";

	}

	/**
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
	 */
	public function getConnection() {
		if (! self::$sqlQueryStatic) {
			self::$sqlQueryStatic = new SQLQuery(DB_DATABASE_TEST);
			self::$sqlQueryStatic->setCredential(DB_USER_TEST, DB_PASSWORD_TEST);
			self::$sqlQueryStatic->setDatabaseHost(DB_HOST_TEST);
			self::$sqlQueryStatic->setClientEncoding(DB_CLIENT_ENCODING);
		}
		$this->objectInstancier = new ObjectInstancier();
		$this->objectInstancier->__set('SQLQuery',self::$sqlQueryStatic);

		//C'est utilisé pour les vieux truc User qui authentifie à l'aide d'un singleton...
		global $sqlQuery;
		$sqlQuery = $this->getSQLQuery();

		return $this->createDefaultDBConnection(self::$sqlQueryStatic->getPdo(), DB_DATABASE_TEST);
	}

	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 */
	public function getDataSet() {
		return new PHPUnit_Extensions_Database_DataSet_YamlDataSet( __DIR__."/database_data.yml");
	}
	
 	protected function getSetUpOperation() {
        return new \PHPUnit_Extensions_Database_Operation_Composite(array(
            \PHPUnit_Extensions_Database_Operation_Factory::DELETE_ALL(),
            \PHPUnit_Extensions_Database_Operation_Factory::INSERT()
        ));
    }
    
    public function getObjectInstancier(){
    	return $this->objectInstancier;
    }

	/**
	 * @return SQLQuery
	 */
    public function getSQLQuery(){
    	return self::$sqlQueryStatic;
    }
    
    public function setSuperAdminAuthentication(){
    	$_SERVER['SSL_CLIENT_VERIFY'] = "SUCCESS";
    	$_SERVER['SSL_CLIENT_S_DN'] = "test_subject";
    	$_SERVER['SSL_CLIENT_I_DN'] = "test_issuer";
		$_SERVER['TESTING_CERTIFICATE_HASH'] = "q2UZmkpQTMgJgyQBfsnw40wOUCvH7SVy54EVEcgq9kc=";
    }
    
    public function setAdminGroupAuthentication(){
    	$_SERVER['SSL_CLIENT_VERIFY'] = "SUCCESS";
    	$_SERVER['SSL_CLIENT_S_DN'] = "admin_groupe";
    	$_SERVER['SSL_CLIENT_I_DN'] = "admin_groupe";
		$_SERVER['TESTING_CERTIFICATE_HASH'] = "hash_admin_groupe";
	}
    
    public function setAdminCol2Authentication(){
    	$_SERVER['SSL_CLIENT_VERIFY'] = "SUCCESS";
    	$_SERVER['SSL_CLIENT_S_DN'] = "admin_col2";
    	$_SERVER['SSL_CLIENT_I_DN'] = "admin_col2";
		$_SERVER['TESTING_CERTIFICATE_HASH'] = "hash_admin_col2";
    }

	public function setUserAuthentification(){
		$_SERVER['SSL_CLIENT_VERIFY'] = "SUCCESS";
		$_SERVER['SSL_CLIENT_S_DN'] = "user_col1";
		$_SERVER['SSL_CLIENT_I_DN'] = "user_col1";
		$_SERVER['TESTING_CERTIFICATE_HASH'] = "hash_user_col1";
	}

	
}
