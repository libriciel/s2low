<?php

class AdminUserController extends Controller {

	/**
	 * @var UserSQL
	 */
	private $userSQL;

	public function __construct(ObjectInstancier $objectInstancier){
		parent::__construct($objectInstancier);
		$this->userSQL = new UserSQL($this->getSQLQuery());
	}

	private function getFromFile($name){
		if (empty($_FILES[$name])) {
			return false;
		}
		if (! is_uploaded_file_wrapper($_FILES[$name]['tmp_name'])){
			return false;
		}
		return file_get_contents($_FILES[$name]['tmp_name']);
	}

	private function doEdit($my_user_id, Recuperateur $recuperateur){
		$api = $recuperateur->getInt('api');

		$user_id = $recuperateur->getInt('id');
		$user_id_a_cloner = $recuperateur->getInt('new_id');

		$authority_id = $recuperateur->getInt('authority_id');
		$role = $recuperateur->get('role');
		$authority_group_id = $recuperateur->get('authority_group_id');
		$login = $recuperateur->get('login');
		$password = $recuperateur->get('password');
		$password2 = $recuperateur->get('password2');

		if ($api && ! $authority_id){
			//Il faut penser au cas où on on est en modification et ou on passe pas l'authority_id... c'est  nul...
			throw new Exception("authority_id est obligatoire");
		}

		if ($role == 'GADM' && ! $authority_group_id){
			throw new Exception("Vous devez indiquez un groupe pour créer un administrateur de groupe");
		}

		if (! $api && $password != $password2){
			throw new Exception("Les mots de passe ne correspondent pas");
		}

		$x509Certificate = new X509Certificate();

		$user_info = false;
		if ($user_id){
			$userSQL = new UserSQL($this->getSQLQuery());
			$user_info = $userSQL->getInfo($user_id);
			if (!$user_info){
				throw new Exception("Erreur lors de la modification de l'utilisateur");
			}

		} elseif($user_id_a_cloner) {
			$userSQL = new UserSQL($this->getSQLQuery());
			$user_info = $userSQL->getInfo($user_id_a_cloner);
			if (!$user_info){
				throw new Exception("Erreur lors du clonage de l'utilisateur");
			}
			if (! $login || ! $password){
				throw new Exception("Le login et le mot de passe sont obligatoire pour cloner un certificat");
			}
		}

		if ($user_info){
			$certificat_connexion_info = $x509Certificate->getInfo($user_info['certificate']);
			$certificate_rgs_2_etoiles_clean_content = $user_info['certificate_rgs_2_etoiles'];
		} else {
			$certificat_connexion_info = false;
			$certificate_rgs_2_etoiles_clean_content = false;
		}


		$certificat_connexion = $this->getFromFile('certificate');

		if ($certificat_connexion){
			$certificat_connexion_info = $x509Certificate->getInfo($certificat_connexion);
		}

		if ( ! $certificat_connexion_info ) {
			throw new Exception("Le certificat utilisateur est obligatoire");
		}

		$certificate_rgs_2_etoiles = $this->getFromFile('certificate_rgs_2_etoiles');
		if ($certificate_rgs_2_etoiles){
			$certificate_rgs_2_etoiles_clean_content = $x509Certificate->pemClean($certificate_rgs_2_etoiles);
		}

		if ($this->userSQL->hasDoublon($user_id,$certificat_connexion_info,$login,$certificate_rgs_2_etoiles_clean_content)){
			throw new Exception("Un utilisateur avec les même information de connexion et d'identification existe dans la base S2low");
		}

		//OK ALL GOOD !
	}

	public function doEditAction(){
		$this->verifAdmin();
		$my_user_id = $this->me->getId();

		$api = Helpers::getVarFromPost("api");
		$id = Helpers::getVarFromPost("id");
		$name = Helpers::getVarFromPost("name", true);
		$givenname = Helpers::getVarFromPost("givenname", true);
		$email = trim(Helpers::getVarFromPost("email", true));
		$telephone = Helpers::getVarFromPost("telephone", true);
		$status = Helpers::getVarFromPost("status", true);
		$authority_id = Helpers::getVarFromPost("authority_id", true);
		$role = Helpers::getVarFromPost("role", true);
		$authority_group_id = Helpers::getVarFromPost("authority_group_id", true);
		$login = Helpers::getVarFromPost("login", true);
		$password = Helpers::getVarFromPost("password", true);
		$password2 = Helpers::getVarFromPost("password2", true);
		$new_id = Helpers::getVarFromPost("new_id", true);

		$auth_method = Helpers::getVarFromPost("",true);


		$certificate = $_FILES["certificate"];
		$certificate_rgs_2_etoiles = $_FILES['certificate_rgs_2_etoiles'];



		$me = new User();

		if (! $me->authenticate()) {
			$this->displayErrorAndExit("Échec de l'authentification","/");
		}

		if (! $me->isAdmin()) {
			$this->displayErrorAndExit("Accès refusé","/");
		}

		$myAuthority = new Authority($me->get("authority_id"));


		$him = new User();
		$mod = false;

		if ($id) {
			$him->setId($id);
			if (! $him->init()) {
				$this->displayErrorAndExit("Erreur lors de la modification de l'utilisateur","/admin/users/admin_users.php");
			}

			// On vérifie que l'utilisateur courant à le droit de modifier cet utilisateur
			if (! $me->canEditUser($id)) {
				$this->displayErrorAndExit("Accès refusé pour la modification de cet utilisateur", "/admin/users/admin_users.php");
			}
			$mod = true;
		}


		try {
			$this->doEdit($my_user_id, $this->getRecuperateurPost());
		} catch (Exception $e){
			$this->displayErrorAndExit($e->getMessage(),"/admin/users/admin_user_edit.php?id=$id&new_id=$new_id");
		}


		$him->set("name", $name);
		$him->set("givenname", $givenname);
		$him->set("email", $email);
		$him->set("telephone", $telephone);
		$him->set("status", $status);
		$him->set("login",$login);
		if ($password){
			$him->set("password",md5($password));
		}


		// Le groupe d'appartenance pour un administrateur de groupe
		if ($me->isSuper()) {
			$him->set("authority_group_id", $authority_group_id);
		}

		// Traitement du certificat
		if (is_array($certificate) && count($certificate) > 0 && is_uploaded_file_wrapper($certificate["tmp_name"])) {
			$him->set("certFilePath", $certificate["tmp_name"]);
		}

		if ($new_id){
			if ($him->getIdFromLogin($login)){
				$this->displayErrorAndExit("Ce login est déja utilisé","/admin/users/admin_user_edit.php?new_id=$new_id");
			}
			$him->cloneCertificat($new_id);
		} else {
			if ($login){
				$the_id = $him->getIdFromLogin($login);

				if ($the_id && $id != $the_id){
					$this->displayErrorAndExit("Ce login est déja utilisé", "/admin/users/admin_users.php");
				}
			}
		}



		if (! $mod || $new_id) {

			if ($me->isSuper()) {
				$him->set("authority_id", $authority_id);

			} elseif ($me->isGroupAdmin()) {
				$authority = new Authority($authority_id);

				if ($authority->isInGroup($me->get("authority_group_id"))) {
					$him->set("authority_id", $authority_id);
				} else {
					$this->displayErrorAndExit("La collectivité n'appartient pas au groupe courant",
						"/admin/users/admin_users.php");
				}
			} else {
				// Un admin de collectivité ne peut créer que des utilisateurs appartenant à sa collectivité
				$him->set("authority_id", $myAuthority->getId());
			}
		}

		// Les admins de collectivité et de groupe ne peuvent créer que des administrateurs de collectivité
		// ou des utilisateurs simples
		if (! $me->isSuper()) {
			if (strcasecmp($role, 'SADM') == 0 || strcasecmp($role, 'GADM') == 0) {
				$role = 'ADM';
			}
		}

		$him->set("role", $role);

		if (! $him->get('authority_id')){
			$this->displayErrorAndExit("authority_id est obligatoire","/admin/users/admin_users.php");
		}


		// Permissions sur les modules
		// Récupération des modules actifs globalement
		$modules = Module::getActiveModulesList();
		// Récupération des modules authorisés pour la collectivité
		$authModules = Module::getModulesForAuthority($him->get("authority_id"));

		$him->resetPerms();

		foreach ($modules as $module) {
			if (isset($authModules[$module["id"]]) && ($authModules[$module["id"]] || $him->isGroupAdmin())) {
				$him->setPerm($module["id"], Helpers::getVarFromPost("perm_" . $module["id"]),$module['specific_perms']);
			}
		}

		if (! $him->save()) {
			$msg = "Erreur lors de l'enregistrement de l'utilisateur :\n" . $him->getErrorMsg();
			if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, $me->get("role"), false, $me)) {
				$msg .= "\nErreur de journalisation.";
			}


			if ($him->isNew()) {
				$location =  WEBSITE_SSL . "/admin/users/admin_user_edit.php?new_id=$new_id";
			} else {
				Helpers::purgeTempSession();
				$location = WEBSITE_SSL . "/admin/users/admin_user_edit.php?id=" . $him->getId()."&new_id=$new_id";
			}

			$this->displayErrorAndExit(nl2br($msg), $location);

		}

		$msg = ($mod) ? "Modification" : "Création";
		$msg .= " de l'utilisateur " . $him->getPrettyName() . " (id=" . $him->getId() . "). Résultat ok.";

		$userSQL = new UserSQL($this->getSQLQuery());

		if (is_array($certificate_rgs_2_etoiles) && count($certificate_rgs_2_etoiles) > 0 && is_uploaded_file_wrapper($certificate_rgs_2_etoiles["tmp_name"])) {

			$certificate_rgs_2_etoiles_content = file_get_contents($certificate_rgs_2_etoiles["tmp_name"]);

			$x509Certificate = new X509Certificate();
			try {
				$certificate_rgs_2_etoiles_content = $x509Certificate->pemClean($certificate_rgs_2_etoiles_content);
				$userSQL->saveCertificateRGS2Etoiles($him->getId(),$certificate_rgs_2_etoiles_content);
			} catch(Exception $e){
				$msg .= "\nLe certificat rgs_2_etoile n'est pas au bon format\n";
			}

		} else {
			$userSQL->updateCertificatRGS2EtoilesIfNull($him->getId());
		}


		if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
			$msg .= "\nErreur de journalisation.";
		}

		if ($api){
			$jsonOutput = new JSONoutput();
			$jsonOutput->displayAndExit(array('status'=>'ok','message'=>$msg,'id'=>$him->getId()));
		} else {
			$_SESSION["error"] = nl2br($msg);
			Helpers::purgeTempSession();
			if (TESTING_ENVIRONNEMENT){
				return $him->getId();
			} else {
				$this->redirectSSL("/admin/users/admin_user_edit.php?id=" . $him->getId());
			}
		}

	}


}