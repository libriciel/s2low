<?php


class AdminServiceController extends Controller {

	/**
	 * @throws RedirectException
	 */
	public function addAction(){
		$this->verifAdmin();

		$name =  $this->getEnvironnement()->post()->get('name');
		$authority_id =  $this->getEnvironnement()->post()->get('authority_id');

		if ($authority_id){
			$this->verifAdmin($authority_id);
		} else {
			$authority_id = $this->me->get('authority_id');
		}

		$url_redirect = "/admin/services/admin_services.php?authority_id=$authority_id";

		if ( ! $name ) {
			$this->displayErrorAndExit("Le nom du service est obligatoire",$url_redirect);
		}

		$serviceUser = $this->getObjectInstancier()->get(ServiceUser::class);
		$result = $serviceUser->add($name,$authority_id);

		if (! $result){
			$this->displayErrorAndExit("Ce service existe déjà !",$url_redirect);
		}

		$this->displayAndExit("Le service a été créé",$url_redirect);
	}


	public function listAction(){
		$authority_id =  $this->getEnvironnement()->get()->get('authority_id');
		$this->verifAdmin($authority_id);
		$serviceUser = $this->getObjectInstancier()->get(ServiceUser::class);
		$result = $serviceUser->getServiceUser($authority_id);
		echo json_encode(utf8_encode_array($result));
		exit_wrapper();
	}

	public function addUserAction(){
		$serviceUser = $this->getObjectInstancier()->get(ServiceUser::class);

		$id_service =  $this->getEnvironnement()->post()->get('id_service');
		$id_user =  $this->getEnvironnement()->post()->get('id_user');

		$info = $serviceUser->getInfo($id_service);


		$this->verifAdmin($info['authority_id']);


		$userSQL = $this->getObjectInstancier()->get(UserSQL::class);
		$user_info = $userSQL->getInfo($id_user);

		$this->verifAdmin($user_info['authority_id']);

		$serviceUser->addUser($id_user,$id_service);

		$url_redirect = "/admin/users/admin_user_edit.php?id=$id_user";
		$this->displayAndExit("L'utilisateur a été ajouté au service",$url_redirect);
	}


}