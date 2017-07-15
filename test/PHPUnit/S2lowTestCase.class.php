<?php


abstract class S2lowTestCase extends PHPUnit_Extensions_Database_TestCase {

	/**
	 * @var SQLQuery
	 */
	private static $sqlQueryStatic;

	//private $objectInstancier;

	protected $backupGlobalsBlacklist = array('sqlQuery');

	protected function setUp(){
		parent::setUp();

		//Bon, c'est sale, mais le fichier YML est forcément en UTF-8... (voir plus bas)
		$this->getSQLQuery()->query("SET CLIENT_ENCODING TO 'LATIN9';");
		$this->getSQLQuery()->query("SELECT SETVAL('users_id_seq', (SELECT MAX(id)+1 FROM users))");
		$this->getSQLQuery()->query("SELECT SETVAL('authorities_id_seq', (SELECT MAX(id)+1 FROM authorities))");
		$this->getSQLQuery()->query("SELECT SETVAL('authority_groups_id_seq', (SELECT MAX(id)+1 FROM authority_groups))");

		$_GET = array();
		$_POST = array();
		$_SESSION = array();
		$_SERVER['SSL_CLIENT_VERIFY'] = "";
		$_SERVER['SSL_CLIENT_S_DN'] = "";
		$_SERVER['SSL_CLIENT_I_DN'] = "";
		$_SERVER['SSL_CLIENT_CERT'] = "";
		$_SERVER["QUERY_STRING"] = "";

        ObjectInstancierFactory::setObjectInstancier(new ObjectInstancier());
        $this->getObjectInstancier()->__set('SQLQuery',$this->getSQLQuery());
        $this->getObjectInstancier()->set('helios_files_upload_root',"/tmp");
        $this->getObjectInstancier()->set("openstack_authentication_url_v2","");
        $this->getObjectInstancier()->set("openstack_username","a");
        $this->getObjectInstancier()->set("openstack_password","a");
        $this->getObjectInstancier()->set("openstack_tenant","a");
        $this->getObjectInstancier()->set("openstack_region","a");
        $this->getObjectInstancier()->set("openstack_swift_container_prefix","a");

        $get = array();
        $post = array();
        $request = array();
        $session = array();
        $server = array();

        $this->getObjectInstancier()->set('Environnement',new Environnement($get,$post,$request,$session,$server));

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

        //Bon, c'est sale, mais le fichier YML est forcément en UTF-8... (voir plus haut)
        self::$sqlQueryStatic->query("SET CLIENT_ENCODING TO 'UTF-8';");
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

	/**
	 * @return ObjectInstancier
	 */
    public function getObjectInstancier(){
        return  ObjectInstancierFactory::getObjetInstancier();
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

    public function setAdminColAuthentication(){
        $_SERVER['SSL_CLIENT_VERIFY'] = "SUCCESS";
        $_SERVER['SSL_CLIENT_S_DN'] = "admin_col1";
        $_SERVER['SSL_CLIENT_I_DN'] = "admin_col1";
        $_SERVER['TESTING_CERTIFICATE_HASH'] = "admin_col1";
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
