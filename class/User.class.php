<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à   la
 * dématérialisation de l'administration. 
 *
 * Ce logiciel est régi par la licence CeCILL soumise au droit français et
 * respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusée par le CEA, le CNRS et l'INRIA 
 * sur le site "http://www.cecill.info".
 *
 * En contrepartie de l'accessibilité au code source et des droits de copie,
 * de modification et de redistribution accordés par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitée.  Pour les mêmes raisons,
 * seule une responsabilité restreinte pèse sur l'auteur du programme,  le
 * titulaire des droits patrimoniaux et les concédants successifs.
 *
 * A cet égard  l'attention de l'utilisateur est attirée sur les risques
 * associés au chargement,  à   l'utilisation,  à   la modification et/ou au
 * développement et à   la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à   
 * manipuler et qui le réserve donc à   des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à   charger  et  tester  l'adéquation  du
 * logiciel à   leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * à  l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder à cet en-tte signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/
?>
<?php
/**
 * \class User User.class.php
 * \brief Classe pour la gestion des utilisateurs
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 15.02.2006
 * 
 *
 * Cette classe fournit des méthodes de gestion des utilisateurs
 * enregistrement, suppression, génération des certificats
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *  JS   17.07.2006  Adaptation pour Tedetis
 */

require_once("DataObject.class.php");
require_once("Authority.class.php");

class User extends DataObject {
	
	
	const PERM_MODIFICATION = "RW";
	
	
  protected $objectName = "users";
  protected $prettyName = "Utilisateur";

  protected $email;
  protected $subject_dn;
  protected $issuer_dn;
  protected $name;
  protected $givenname;
  protected $telephone;
  protected $role;
  protected $authority_group_id;
  protected $authority_id;
  protected $status;
  protected $certificate;
  protected $cert_not_before;
  protected $cert_not_after;
  protected $cert_serial;

  private $perms;

  protected $certFilePath;
  protected $certPassphrase;

  protected $dbFields = array( "email" => array( "descr" => "Adresse électronique", "type" => "isEmail", "mandatory" => true),
						 "subject_dn" => array( "descr" => "Dn du certificat", "type" => "isString", "mandatory" => true),
						 "issuer_dn" => array( "descr" => "DN du fournisseur du certificat", "type" => "isString", "mandatory" => true),
						 "name" => array( "descr" => "Nom", "type" => "isString", "mandatory" => true),
						 "givenname" => array( "descr" => "Prénom", "type" => "isString", "mandatory" => true),
 						 "role" => array( "descr" => "Rôle", "type" => "isString", "mandatory" => true),
 						 "telephone" => array( "descr" => "Téléphone", "type" => "isString", "mandatory" => false),
						 "authority_group_id" => array( "descr" =>  "Groupe", "type" => "isInt", "mandatory" => false),
						 "authority_id" => array( "descr" =>  "Collectivité", "type" => "isInt", "mandatory" => true),
						 "status" => array( "descr" => "État", "type" => "isInt", "mandatory" => true),
						 "certificate" => array( "descr" => "Certificat de l'utilisateur", "type" => "isString", "mandatory" => false),
						 "cert_not_before" => array( "descr" => "Date d'émission du certificat", "type" => "isDate", "mandatory" => true),
						 "cert_not_after" => array( "descr" => "Date d'expiration du certificat", "type" => "isDate", "mandatory" => true),
						 "cert_serial" => array( "descr" => "Numéro de série du certificat", "type" => "isDate", "mandatory" => true),
  							"login" => array("descr" => "login","type"=>"isString","mandatory"=>false),	
  							"password" => array("descr" => "password","type"=>"isString","mandatory"=>false),
  
						 );

  protected $roleTypes = array(
							   "SADM" => "Super administrateur",
							   "GADM" => "Administrateur de groupe",
							   "ADM" => "Administrateur collectivité",
							   "USER" => "Utilisateur"
							   );

  protected $permsTypes = array(
								"NONE" => "Aucune",
								"RO" => "Visualisation",
								"RW" => "Modification",
								);

  protected $superPermsTypes = array(
									 "GRANT" => "Concession"
									 );

  /**
   * \brief Constructeur d'un utilisateur
   * \param $id integer (optionnel) : numéro d'identifiant de l'utilisateur
  */
  public function __construct($id = false) {
    parent::__construct($id);
  }

  /**
   * \brief Méthode d'initialisation d'un utilisateur depuis la base de données
   * \return true si succès, false sinon
  */
  public function init() {
	return (parent::init() && $this->initPerms());
  }

  /**
   * \brief Méthode permettant de fixer la valeur d'un attribut
   * \param $name chaîne : Nom de l'attribut
   * \param $val : valeur de l'attribut
  */
  public function set($name, $val) {
	switch ($name) {
	case "role":
	  if (array_search($val, array_keys($this->roleTypes)) === false) {
		$val = 'USER';
	  }
	  break;
	}

	parent::set($name, $val);
  }

  /**
   * \brief Méthode permettant de récupérer la valeur d'un attribut
   * \param $name chaîne : Nom de l'attribut
  */
  public function get($name) {
	switch ($name) {
	case "permsTypes":
	  if ($this->isSuper()) {
		return array_merge($this->permsTypes, $this->superPermsTypes);
	  } else {
		return $this->permsTypes;
	  }
	  break;
	default:
	  return parent::get($name);
	  break;
	}
  }

  /**
   * \brief Méthode d'authentification de l'utilisateur
   * \return true si succès, false sinon
   *
   * Cette méthode vérifie qu'un utilisateur est bien autorisé à se connecter au système.
   * Elle se base sur les données du certificat présenté au serveur Web pour authentifier
   * et initialiser les données de l'utilisateur.
  */
	public function authenticate() {
	  	
		$this->retrieveInfoFromClientCertificate();
		$ids = $this->getIdFromCertData($this->subject_dn, $this->issuer_dn);
		
		if (! $ids) {
			return false;
		}
		
		if (count($ids) > 1 && ! $this->isLogged()){
			header("Location: " . WEBSITE_SSL."/login.php");
  			exit();
		}
		
		if (count($ids) > 1){
			$this->id = $_SESSION['id_login'];
			$_SESSION['nb_id'] = count($ids);			
		} else {		
	    	$this->id = $ids[0];
	    	$_SESSION['id_login'] = $this->id;
	    	$_SESSION['nb_id'] = 1;			
		}
		
		return ($this->init() && $this->isActive());
	        
	}

	public function isLogged(){
		return isset($_SESSION['id_login']) && $_SESSION['id_login'] ;
	}
	
	public function login($login,$password){
		$this->retrieveInfoFromClientCertificate();
		 $sql = "SELECT id FROM users WHERE subject_dn='" . pg_escape_string($this->subject_dn) . "' AND issuer_dn='" . pg_escape_string($this->issuer_dn) . "'" .
                        " AND login='".pg_escape_string($login)."' AND password='".pg_escape_string($password)."'";
	 	$result = $this->db->select($sql);
		if ($result->isError() || $result->num_row() != 1){
			$this->errorMsg = "User::getIdFromCertData - Échec du mappage de l'utilisateur depuis les informations du certificat";
			return false;
		}
		
   		$row = $result->get_next_row();
		$this->id = $row['id'];
		$_SESSION['id_login'] = $this->id;
		return true;
	}
  
	public function logout(){
		unset($_SESSION['id_login']);
	}
	
	private function retrieveInfoFromClientCertificate(){
		 // Ne marche pas avec apache-ssl
		if ( ! isset($_SERVER['SSL_CLIENT_VERIFY']) || $_SERVER['SSL_CLIENT_VERIFY'] != "SUCCESS") {
			return false;
		}
		$this->subject_dn = $_SERVER['SSL_CLIENT_S_DN'];
		
		
		if (empty($_SERVER['SSL_CLIENT_CERT'])){
		    $this->issuer_dn = $_SERVER['SSL_CLIENT_I_DN'];		
			return ;
		}
		
		if (($tab = openssl_x509_parse($_SERVER['SSL_CLIENT_CERT'])) === false) {
	       	return false;
		}
		
        
		// Si l'utilisateur est authentifié par certificat
	    $this->issuer_dn = "";
        foreach ($tab['issuer'] as $key => $val) {
        	$this->issuer_dn .= "/" . $key . "=" . utf8_decode($val);
		}	
	}
	
  /**
   * \brief Méthode de réinitialisation de la session d'un utilisateur
  */
  public function resetSession() {
    $_SESSION = array();

    $sessionCookie = session_get_cookie_params();
    
    if ( (empty($sessionCookie['domain'])) && (empty($sessionCookie['secure'])) ) {
      setcookie(session_name(), '', time()-3600, $sessionCookie['path']);
    } elseif (empty($sessionCookie['secure'])) {
      setcookie(session_name(), '', time()-3600, $sessionCookie['path'], $sessionCookie['domain']);
    } else {
      setcookie(session_name(), '', time()-3600, $sessionCookie['path'], $sessionCookie['domain'], $sessionCookie['secure']);
    }

    session_destroy();
  }

  /**
   * \brief Méthode de récupération de l'identifiant d'un utilisateur d'après les données de son certificat
   * \param $subject_dn chaîne : DN du sujet du certificat
   * \param $issuer_dn chaîne : DN de l'emetteur du certificat
   * \return L'identifiant si succès, false sinon
  */
  public function getIdFromCertData($subject_dn, $issuer_dn) {
  	$resultat = array();
  	
	$subject_dn = str_replace('\\', '\\\\', $subject_dn);
	$subject_dn = str_replace('\'', '\\\'', $subject_dn);
	$issuer_dn = str_replace('\\', '\\\\', $issuer_dn);
	$issuer_dn = str_replace('\'', '\\\'', $issuer_dn);

    $sql = "SELECT id FROM users WHERE subject_dn='" . $subject_dn . "' AND issuer_dn='" . $issuer_dn . "'";

    $result = $this->db->select($sql);
	if ($result->isError() || $result->num_row() == 0){
		$this->errorMsg = "User::getIdFromCertData - Échec du mappage de l'utilisateur depuis les informations du certificat";
		return false;
	}
	
   	while ($row = $result->get_next_row()){
		$resultat[] = $row['id'];      	
	}
	return $resultat;
 
  }

  /**
   * \brief Méthode qui détermine si l'utilisateur est un administrateur, un administrateur de groupe ou un super administrateur
   * \return true si l'utilisateur est administrateur, administrateur de groupe ou super administrateur, false sinon
  */
  public function isAdmin() {
	if (isset($this->role) && ($this->role == "SADM" || $this->role == "GADM" || $this->role == "ADM")) {
	  return true;
	} else {
	  return false;
	}
  }

  /**
   * \brief Méthode qui détermine si l'utilisateur est un administrateur de collectivité
   * \return true si l'utilisateur est administrateur de collectivité, false sinon
  */
  public function isAuthorityAdmin() {
	if (isset($this->role) && $this->role == "ADM") {
	  return true;
	} else {
	  return false;
	}
  }

  /**
   * \brief Méthode qui détermine si l'utilisateur est un administrateur de groupe
   * \return true si l'utilisateur est administrateur de groupe, false sinon
  */
  public function isGroupAdmin() {
	if (isset($this->role) && ($this->role == "GADM") && is_numeric($this->authority_group_id)) {
	  return true;
	} else {
	  return false;
	}
  }

  /**
   * \brief Méthode qui détermine si l'utilisateur est un super administrateur
   * \return true si l'utilisateur est super administrateur, false sinon
  */
  public function isSuper() {
    if ( isset($this->role) && $this->role == 'SADM') {
	  return true;
	} else {
	  return false;
	}
  }

  /**
   * \brief Méthode qui détermine si l'utilisateur est un administrateur de groupe ou un super administrateur
   * \return true si l'utilisateur est administrateur de groupe ou super administrateur, false sinon
  */
  public function isGroupAdminOrSuper() {
	return ($this->isGroupAdmin() || $this->isSuper());
  }

  /**
   * \brief Méthode qui détermine si l'utilisateur est activé ou non
   * \return true si l'utilisateur est activé, false s'il est désactivé
  */
  public function isActive() {
	$authority = new Authority($this->authority_id);

	// Prise en compte du groupe
	if (is_numeric($authority->get("authority_group_id"))) {
	  $group = new Group($authority->get("authority_group_id"));
	  $groupIsActive = $group->isActive();
	} else {
	  // La collectivité n'appartient à aucun groupe
	  $groupIsActive = true;
	}

    return ($this->status == 1 && $authority->isActive() && $groupIsActive);
  }

  /**
   * \brief Méthode qui détermine si l'utilisateur courant à les droits pour modifier un autre utilisateur
   * \param $id integer : Numéro d'identifiant de l'utilisateur a éditer
   * \return true si l'utilisateur peut modifier, false sinon
  */
  public function canEditUser($id) {
	$sql = "SELECT authority_id FROM users WHERE id='" . $id . "'";

    $result = $this->db->select($sql);

    if (! $result->isError() && $result->num_row() == 1) {
      $row = $result->get_next_row();

	  $authority = new Authority($row["authority_id"]);

	  // Prise en compte du groupe
	  if (is_numeric($authority->get("authority_group_id"))) {
		$group = new Group($authority->get("authority_group_id"));
		$inGroup = $this->role == "GADM" && $this->authority_group_id == $group->getId();
	  } else {
		// La collectivité n'appartient à aucun groupe
		$inGroup = false;
	  }

      return ($this->role == "SADM" || $inGroup || ($row["authority_id"] == $this->authority_id && $this->role == "ADM"));
    } else {
      $this->errorMsg = "User::canEditUser - erreur de résultat requête base de données";
      return false;
    }
  }

  /**
   * \brief Méthode qui détermine si l'utilisateur courant à les droits pour concéder des droits sur un module
   * \param $name chaine : Nom du module
   * \return true si l'utilisateur peut concéder, false sinon
  */
  public function canGrantModule($name) {
	if (strcmp($this->getPerm($name), "GRANT") == 0) {
	  return true;
	} else {
	  return false;
	}
  }

  /**
   * \brief Méthode retournant la description du rôle de l'utilisateur en cours
   * \return La description ou une chaîne vide si la decsription n'est pas trouvée
  */
  public function getRoleDescr() {
    return (isset($this->role)) ? $this->roleTypes[$this->role] : "";
  }

  /**
   * \brief Méthode retournant les permissions de l'utilisateur en cours sur les modules
   * \return Un tableau de permissions ou null si les permissions ne sont pas définies
  */
  public function getPerms() {
    return (isset($this->perms)) ? $this->perms : null;
  }

  /**
   * \brief Méthode retournant les permissions de l'utilisateur sur un module particulier
   * \param $module chaîne : nom du module pour lequel récupérer les permissions
   * \return La chaîne des permissions sur le module ou null si aucune permission trouvée
  */
  public function getPerm($module) {
	return (isset($this->perms[$module])) ? $this->perms[$module]["perm"] : null;
  }

  /**
   * \brief Méthode déterminant si un utilisateur peut accéder à une page
   * \param $module chaîne : nom du module
   * \return True si l'utilisateur peut accéder ou false sinon
  */
  public function canAccess($module) {
  	if ($this->isGroupAdminOrSuper()) {
  		return true;
  	} 	
	  // La collectivité a-t'elle accès au module
	  $authority = new Authority($this->authority_id);

	  $mod = new Module();
	  if (! $mod->initByName($module)) {
		// Erreur
		return false;
	  }

	  if (! $authority->getModulePerm($mod->getId())) {
		// Pas d'accès à ce module
		return false;
	  }
// bug 185 le amdmin de la collectivité peut access au module même pas d'autority.	  
//	  if ($this->getPerm($module) == "RO" || $this->getPerm($module) == "RW" || $this->isAdmin()) {
	  if ($this->getPerm($module) == "RO" || $this->getPerm($module) == "RW" ) {
	 	return true;
	  }
	
  }

  /**
   * \brief Méthode déterminant si un utilisateur à accès en modification
   * \param $module chaîne : nom du module
   * \return True si l'utilisateur peut modifier ou false sinon
  */
  public function canEdit($module) {
	if (! $this->isGroupAdminOrSuper()) {
	  // La collectivité a-t'elle accès au module
	  $authority = new Authority($this->authority_id);

	  $mod = new Module();
	  if (! $mod->initByName($module)) {
		// Erreur
		return false;
	  }

	  if (! $authority->getModulePerm($mod->getId())) {
		// Pas d'accès à ce module
		return false;
	  }
//	  if ($this->getPerm($module) == "RW" || $this->isAdmin()) {  
	  if ($this->getPerm($module) == "RW") {
		return true;
	  }
	} else {
	  return true;
	}
  }

  /**
   * \brief Méthode retournant les types de permissions
   * \return Le tableau des types de permissions
  */
  public function getPermsTypes() {
	return $this->permsTypes;
  }

  /**
   * \brief Méthode permettant de fixer les permissions d'un utilisateur sur un module
   * \param $module_id integer : numéro d'identifiant du module dont fixer les permissions
   * \param $perm chaîne : permission sur le module
   * \return true si succès, false sinon
  */
  public function setPerm($module_id, $perm) {
	$module = new Module($module_id);

	if (! $module->init()) {
	  return false;
	}

	if (array_search($perm, array_keys(array_merge($this->superPermsTypes, $this->permsTypes))) === false) {
	  $perm = "NONE";
	}

	$this->perms[$module->get("name")] = array("module_id" => $module->getId(), "perm" => $perm);

	return true;
  }

  /**
   * \brief Méthode de remise à zéro des permission de l'objet utilisateur courant
  */
  public function resetPerms() {
	$this->perms = array();
  }

  /**
   * \brief Méthode d'initialisation des permissions d'un utilisateur depuis la base de données
   * \return true si succès, false sinon
  */
  public function initPerms() {
	if (isset($this->id)) {
	  $this->resetPerms();

	  $authModules = Module::getModulesForAuthority($this->authority_id);

	  $sql = "SELECT users_perms.id, users_perms.module_id, users_perms.perm, modules.name " .
	  		" FROM users_perms LEFT JOIN modules ON users_perms.module_id=modules.id " .
	  		" WHERE users_perms.user_id='" . $this->id . "' AND modules.status=1";

	  $result = $this->db->select($sql);

	  if (! $result->isError()) {
		while ($row = $result->get_next_row()) {
		  // Ajout de la permission uniquement si la collectivité est autorisée sur ce module
		  if ($this->isGroupAdminOrSuper() || $authModules[$row["module_id"]]) {
			$this->perms[$row["name"]] = array("module_id" => $row["module_id"], "perm" => $row["perm"], "id" => $row["id"]);
		  }
		}
	  } else {
		$this->errorMsg = "User::initPerms - erreur de récupération des permissions de l'utilisateur";
		return false;
	  }

	}

	return true;
  }

  /**
   * \brief Méthode renvoyant le nom d'un utilisateur formatté "Prénom Nom"
   * \return La chaîne du nom de l'utilisateur
  */
  public function getPrettyName() {
    if (strlen($this->name) > 0) {
      $str = $this->givenname .  " " . $this->name;
    } else {
      $str = $this->login;
    }

    return $str;
  }

  /**
   * \brief Méthode d'enregistrement d'un utilisateur dans la base de données
   * \param $validate booléen (optionnel) Demande la validation ou non des données de l'entité avant enregistrement (défaut : true)
   * \return true si succès, false sinon
  */
  public function save($validate = true,$bouchon_4_strict_standard = true) {
	if (isset($this->certFilePath)) {
	  if (! $this->importCert()) {
		return false;
	  }
	}	
	
    if (! ($sql = parent::save($validate, true))) {
	  return false;
	}

    //echo $sql;
    //exit();

	if (! $this->db->begin()) {
      $this->errorMsg = "Erreur lors de l'initialisation de la transaction.";
      return false;
	}

    if (! $this->db->exec($sql)) {
      $this->errorMsg = "Erreur lors de la sauvegarde de l'utilisateur.";
	  $this->db->rollback();
      return false;
    }

	// Traitement permissions sur les modules
	$sql = "DELETE FROM users_perms WHERE user_id=" . $this->id;

    if (! $this->db->exec($sql)) {
      $this->errorMsg = "Erreur lors de la réinitialisation des permissions de l'utilisateur.";
	  $this->db->rollback();
      return false;
    }

	if (isset($this->perms)) {
	  reset($this->perms);
	  foreach ($this->perms as $perm) {
		$sql = "INSERT INTO users_perms (module_id, user_id, perm) VALUES(" . addslashes($perm["module_id"]) . ", " . $this->id . ", '" . addslashes($perm["perm"]) . "')";
		
		if (! $this->db->exec($sql)) {
		  $this->errorMsg = "Erreur lors de la sauvegarde des permissions de l'utilisateur.";
		  $this->db->rollback();
		  return false;
		}
	  }
	}

	if (! $this->db->commit()) {
      $this->errorMsg = "Erreur lors de la validation de la transaction.";
	  $this->db->rollback();
      return false;
	}

    return true;
  }


  /**
   * \brief Méthode de suppression d'un utilisateur de la base de données
   * \param $id integer (optionnel) Numéro d'identifiant de l'utilisateur, si non spécifié, entité en cours
   * \return true si succès, false sinon
  */
  public function delete($id = false) {
    // Efface l'entité spécifiée par $id ou alors l'entité courante si pas d'id
    if (! $id) {
      if (isset($this->id) && ! empty($this->id)) {
		$id = $this->id;
      } else {
		$this->errorMsg = "Pas d'identifiant pour l'entité a supprimer";
		return false;
      }
    }

	if (! $this->db->begin()) {
      $this->errorMsg = "Erreur lors de l'initialisation de la transaction.";
      return false;
	}

	$sql = "DELETE FROM users_perms WHERE user_id=" . $id;

    if (! $this->db->exec($sql)) {
	  $this->errorMsg = "Erreur lors de la suppression des permissions de l'utilisateur.";
	  $this->db->rollback();
	  return false;
    }

	if (! parent::delete($id)) {
	  $this->db->rollback();
      return false;
	}

	if (! $this->db->commit()) {
      $this->errorMsg = "Erreur lors de la validation de la transaction.";
	  $this->db->rollback();
      return false;
	}

	return true;
  }

  /**
   * \brief Méthode d'import des informations contenus dans le certificat utilisateur
   * \return true si succès, false sinon
  */
  private function importCert() {
	if (isset($this->certFilePath)) {
      if (! $this->certificate = file_get_contents($this->certFilePath)) {
		$this->errorMsg = "Erreur de traitement du certificat.";
		return false;
	  }

      if (($tab = openssl_x509_parse($this->certificate)) === false) {
		$this->errorMsg = "Erreur d'analyse du certificat.";
		return false;
	  }

      //print_r($tab);
      //exit();

	  $this->issuer_dn = "";
	  foreach ($tab['issuer'] as $key => $val) {
		$this->issuer_dn .= "/" . $key . "=" . utf8_decode($val);
	  }

      $this->subject_dn = $tab["name"];
      $this->cert_not_before = ereg_replace('^(..)(..)(..)(..)(..)(..)(.)','20\1-\2-\3 \4:\5:\6 GMT', $tab['validFrom']);
      $this->cert_not_after = ereg_replace('^(..)(..)(..)(..)(..)(..)(.)','20\1-\2-\3 \4:\5:\6 GMT', $tab['validTo']);

      $this->cert_serial = $tab["serialNumber"];

	  // Controle de l'existence d'un utilisateur avec les mêmes données de certificat.
	  // Si un utilisateur a les mêmes données mais qu'il s'agit de l'utilisateur courant
	  // on accepte => permet de modifier le certificat
	  $ids = $this->getIdFromCertData($this->subject_dn, $this->issuer_dn);

	  if ((! empty($ids) && $this->isNew()) || (! empty($ids) && ! $this->isNew() && $ids[0] != $this->id)) {
	  	
	  	if ($ids[0]){
	  		$autre = new User($ids[0]);
	  		$autre->init();
	  		if (! $autre->get('login')){
	  			$this->errorMsg = "Un utilisateur avec les mêmes données de certificat existe déjà. Vous pouvez mettre un login/mot de passe pour les différencier";
  				return false;
	  		}
	  	}
	  	
	  	if ($this->login) {	  		
	  		$id = $this->getIdFromLogin($this->login);
  			if ($id){
  				$this->errorMsg = "Un utilisateur avec le même login existe déjà.";
  				return false;
  			}
	  		
	  		return true;
	  	}
		$this->errorMsg = "Un utilisateur avec les mêmes données de certificat existe déjà. Vous pouvez mettre un login/mot de passe pour les différencier";
		return false;
	  }

	  return true;
	}

	$this->errorMsg = "Pas de certificat fournit.";
	return false;
  }

  /**
   * \brief Méthode de récupération de la liste des utilisateurs
   * \param $cond chaîne (optionnel) : condition à appliquer sur la requête SQL
   * \return Un tableau contenant les données des utilisateurs
  */
  public function getUsersList($cond = "") {
	if (! $this->pagerInit('users.id, users.name, users.givenname, users.email, users.role, users.authority_group_id, users.telephone, users.status, users.authority_id, authorities.name AS authority_name', 'users LEFT OUTER JOIN authorities ON users.authority_id=authorities.id', $cond, 'users.name', null, null, "ASC")) {
	  return false;
	}

    return $this->data;
  }

  /**
   * \brief Méthode de vérification de la présence d'utilisateur dans la base
   * \return True si la base contient au moins un utilisateur, false sinon
  */
  public static function dbHasUser() {
	$sql = "SELECT id FROM users";

    $db =& DatabasePool::getInstance();

	$result = $db->select($sql);

    if (! $result->isError()) {
		  if ($result->num_row() > 0) {
			return true;
		  } else {
			return false;
		  }
    } else {
		  echo $result->error;
		  return false;
		}
  }
  
  public function getUserSiren()
  {
  	$sql ="SELECT siren FROM users, authorities WHERE users.authority_id=authorities.id AND users.id=".$this->id;
    $db =& DatabasePool::getInstance();

  
    $result = $this->db->select($sql);

    if (! $result->isError() && $result->num_row() == 1) {
      $row = $result->get_next_row();
      return $row["siren"];
    } else {
      $this->errorMsg = "User::getUserSiren - Échec du mappage de l'utilisateur depuis les informations du certificat";
      return false;
    }
	}

	public function getIdFromLogin($login){
		$sql = "SELECT id FROM users WHERE users.login='".pg_escape_string($login)."' AND subject_dn='" . pg_escape_string($this->subject_dn) . "' AND issuer_dn='" . pg_escape_string($this->issuer_dn) . "'";
		$result = $this->db->select($sql);
		if ($result->num_row() == 0 ){
			return false;
		}
		$row = $result->get_next_row();
		return $row['id'];
	}

	public function cloneCertificat($new_id){
		$clone = new User($new_id);
		$clone->init();
		foreach (array('subject_dn','issuer_dn','certificate','cert_not_before','cert_not_after','cert_serial') as $info){
			$this->set($info,$clone->get($info));
		}				
	}
	
	public function getAllPossibleAuthority(){
		assert('$this->id');
		if ($this->isSuper()){
			$sql = "SELECT id,name FROM authorities ORDER by name";
		} elseif ($this->isGroupAdmin()){
			$sql = "SELECT id,name FROM authorities WHERE authority_group_id=".$this->get('authority_group_id') . " ORDER by name";
		} else {
			return array($this->get("authority_id") => '');
		}
		$result = array();
		foreach ($this->db->fetchAll($sql) as $r){
			$result[$r['id']] = $r['name'];
		}
		return $result;
	}
	
}

