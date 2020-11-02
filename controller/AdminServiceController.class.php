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

		$serviceUserSQL = $this->getObjectInstancier()->get(ServiceUserSQL::class);
		$result = $serviceUserSQL->add($name,$authority_id);

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

		$info = $serviceUser->getGroupe($id_service);


		$this->verifAdmin($info['authority_id']);


		$userSQL = $this->getObjectInstancier()->get(UserSQL::class);
		$user_info = $userSQL->getInfo($id_user);

		$this->verifAdmin($user_info['authority_id']);

		$serviceUser->addUser($id_user,$id_service);

		$url_redirect = "/admin/users/admin_user_edit.php?id=$id_user";
		$this->displayAndExit("L'utilisateur a été ajouté au service",$url_redirect);
	}

	public function detailAction()
    {
        $serviceUser = $this->getObjectInstancier()->get(ServiceUser::class);
        $service_id =  $this->getEnvironnement()->get()->getInt('id');
        $service_info = $this->verifServiceId($service_id);

        $this->{'users'} = $serviceUser->getListUser($service_id);
        $this->{'all_groupes'} = $serviceUser->getPossibleParent($service_info['authority_id'],$service_id);
        $this->{'serviceEnfant'} = $serviceUser->getAllEnfant($service_id);
        $this->{'id'} = $service_id;
        $this->{'groupe'} = $serviceUser->getGroupe($service_id);
        return true;
    }

    private function verifServiceId($service_id){
        $serviceUserSQL = $this->getObjectInstancier()->get(ServiceUserSQL::class);

        if (! $service_id){
            $this->redirect("/admin/services/admin_services.php","Aucun service trouvé");
        }

        $service_info = $serviceUserSQL->getInfo($service_id);

        if (! $service_info){
            $this->redirect("/admin/services/admin_services.php","Impossible de trouver le service");
        }
        $this->verifAdmin($service_info['authority_id']);
        return $service_info;
    }

    public function addParentAction()
    {
        $serviceUser = $this->getObjectInstancier()->get(ServiceUser::class);

        $id =  $this->getEnvironnement()->post()->getInt('id');
        $this->verifServiceId($id);

        $service_id =  $this->getEnvironnement()->post()->getInt('service_id');
        if ($service_id) {
            $this->verifServiceId($service_id);
        }

        $serviceUser->removeParent($id);
        $groupe = $serviceUser->getGroupe($id);
        $parent = $serviceUser->getPossibleParent($groupe['authority_id'],$id);

        $allParent = array();
        foreach($parent as $service){
            $allParent[] = $service['id'];
        }

        if (in_array($service_id,$allParent)){
            $serviceUser->addParent($id,$service_id);
        }

        $this->redirect("/admin/services/gestion-service-content.php?id=$id","Parent modifié");
    }
}
