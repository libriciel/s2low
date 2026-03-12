<?php

namespace S2lowLegacy\Controller;

use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Model\ServiceUserSQL;
use S2lowLegacy\Model\UserSQL;

class AdminServiceController extends Controller
{
    private const ADMIN_SERVICE_URL = "/admin/services/admin_services.php";

    /**
     * @throws RedirectException
     */
    public function addAction()
    {
        $this->verifAdmin();

        $name =  $this->getEnvironnement()->post()->get('name');
        $authority_id =  $this->getEnvironnement()->post()->get('authority_id');

        if ($authority_id) {
            $this->verifAdmin($authority_id);
        } else {
            $authority_id = $this->userContext->me->get('authority_id');
        }

        $url_redirect = self::ADMIN_SERVICE_URL . "?authority_id=$authority_id";

        if (! $name) {
            $this->displayErrorAndExit("Le nom du service est obligatoire", $url_redirect);
        }

        $serviceUserSQL = $this->getObjectInstancier()->get(ServiceUserSQL::class);
        $result = $serviceUserSQL->add($name, $authority_id);

        if (! $result) {
            $this->displayErrorAndExit("Ce service existe déjà !", $url_redirect);
        }

        $this->displayAndExit("Le service a été créé", $url_redirect);
    }


    public function listAction()
    {
        $authority_id =  $this->getEnvironnement()->get()->get('authority_id');
        $this->verifAdmin($authority_id);
        $serviceUser = $this->getObjectInstancier()->get(ServiceUser::class);
        $result = $serviceUser->getServiceUser($authority_id);
        echo json_encode($result);
        exit_wrapper();
    }

    public function addUserAction()
    {
        $serviceUser = $this->getObjectInstancier()->get(ServiceUser::class);

        $id_service =  $this->getEnvironnement()->post()->get('id_service');
        $id_user =  $this->getEnvironnement()->post()->get('id_user');

        $info = $serviceUser->getGroupe($id_service);


        $this->verifAdmin($info['authority_id']);


        $userSQL = $this->getObjectInstancier()->get(UserSQL::class);
        $user_info = $userSQL->getInfo($id_user);

        $this->verifAdmin($user_info['authority_id']);

        $serviceUser->addUser($id_user, $id_service);

        $url_redirect = "/admin/users/admin_user_edit.php?id=$id_user";
        $this->displayAndExit("L'utilisateur a été ajouté au service", $url_redirect);
    }

    /**
     * @return bool
     * @throws RedirectException
     */
    public function detailAction()
    {
        $serviceUser = $this->getObjectInstancier()->get(ServiceUser::class);
        $service_id =  $this->getEnvironnement()->get()->getInt('id');
        $service_info = $this->verifyServiceId($service_id);

        $this->{'users'} = $serviceUser->getListUser($service_id);
        $this->{'all_groupes'} = $serviceUser->getPossibleParent($service_info['authority_id'], $service_id);
        $this->{'serviceEnfant'} = $serviceUser->getAllEnfant($service_id);
        $this->{'id'} = $service_id;
        $this->{'groupe'} = $serviceUser->getGroupe($service_id);
        return true;
    }

    /**
     * @param $service_id
     * @return false|mixed
     * @throws RedirectException
     */
    private function verifyServiceId($service_id)
    {
        $serviceUserSQL = $this->getObjectInstancier()->get(ServiceUserSQL::class);

        if (! $service_id) {
            $this->redirect(self::ADMIN_SERVICE_URL, "Aucun service trouvé");
        }

        $service_info = $serviceUserSQL->getInfo($service_id);

        if (! $service_info) {
            $this->redirect(self::ADMIN_SERVICE_URL, "Impossible de trouver le service");
        }
        $this->verifAdmin($service_info['authority_id']);
        return $service_info;
    }

    /**
     * @throws RedirectException
     */
    public function addParentAction()
    {
        $serviceUser = $this->getObjectInstancier()->get(ServiceUser::class);

        $id =  $this->getEnvironnement()->post()->getInt('id');
        $this->verifyServiceId($id);

        $service_id =  $this->getEnvironnement()->post()->getInt('service_id');
        if ($service_id) {
            $this->verifyServiceId($service_id);
        }

        $serviceUser->removeParent($id);
        $groupe = $serviceUser->getGroupe($id);
        $parent = $serviceUser->getPossibleParent($groupe['authority_id'], $id);

        $allParent = array();
        foreach ($parent as $service) {
            $allParent[] = $service['id'];
        }

        if (in_array($service_id, $allParent)) {
            $serviceUser->addParent($id, $service_id);
        }

        $this->redirect("/admin/services/gestion-service-content.php?id=$id", "Parent modifié");
    }

    /**
     * @throws RedirectException
     */
    public function enleverUtilisateurAction()
    {
        $id_service =   $this->getEnvironnement()->post()->getInt('id_service');
        $id_users =   $this->getEnvironnement()->post()->getInt('id_user');

        $this->verifyServiceId($id_service);

        if (! $id_users) {
            $this->redirect(
                "/admin/services/gestion-service-content.php?id=$id_service",
                'Il faut sélectionner un utilisateur à enlever du service'
            );
        }

        $serviceUserSQL = $this->getObjectInstancier()->get(ServiceUserSQL::class);
        foreach ($id_users as $id_user) {
            $serviceUserSQL->enleverUser($id_service, intval($id_user));
        }

        $this->redirect(
            "/admin/services/gestion-service-content.php?id=$id_service",
            "L'utilisateur a été retiré du service"
        );
    }

    /**
     * @throws RedirectException
     */
    public function supprimerServiceAction()
    {
        $service_id =   $this->getEnvironnement()->post()->getInt('id');

        $this->verifyServiceId($service_id);
        $serviceUserSQL = $this->getObjectInstancier()->get(ServiceUserSQL::class);
        $serviceUserSQL->supprimerService($service_id);

        $this->redirect(self::ADMIN_SERVICE_URL, "Le service a été supprimé");
    }
}
