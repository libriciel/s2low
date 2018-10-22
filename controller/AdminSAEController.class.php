<?php

class AdminSAEController extends Controller {

	public function _actionBefore($controller,$action){
		$this->verifSuperAdmin();
		parent::_actionBefore($controller,$action);
	}

	public function editAction(){
		$recuperateur = $this->getRecuperateurGet();
		$id = $recuperateur->getInt('id');
		$this->id = $id;
		$this->authorityInfo = $this->getObjectInstancier()->get(AuthoritySQL::class)->getInfo($id);
		$this->pastellProperties = $this->getObjectInstancier()->get(PastellPropertiesSQL::class)->getPastellProperties($id);
		$this->title = "SAE - Configuration";
	}

	/**
	 * @throws RedirectException
	 * @throws Exception
	 */
	public function testAction(){
        $id = $this->getRecuperateurGet()->getInt('id');

		$pastellProperties = $this->getObjectInstancier()->get(PastellPropertiesSQL::class)->getPastellProperties($id);

        $pastellFactory = $this->getObjectInstancier()->get(PastellWrapperFactory::class);
        $pastell = $pastellFactory->getNewInstance($pastellProperties);
		try {
			$pastell->testConnexion();
			$this->setErrorMessage("Connexion OK");
		} catch (Exception $e){
			$this->setErrorMessage($e->getMessage());
		}
        $this->redirect("/admin/authorities/admin_authority_sae.php?id=$id");
    }

	/**
	 * @throws RedirectException
	 */
    public function doEditAction(){
        $id = $this->getRecuperateurPost()->getInt('id');
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = $this->getRecuperateurPost()->get('pastell_url');
        $pastellProperties->login = $this->getRecuperateurPost()->get('pastell_login');
        $pastellProperties->password = $this->getRecuperateurPost()->get('pastell_password');
        $pastellProperties->id_e = $this->getRecuperateurPost()->getInt('pastell_id_e');

        $pastellProperties->actes_flux_id = $this->getRecuperateurPost()->get('actes_flux_id');
        $pastellProperties->actes_action = $this->getRecuperateurPost()->get('actes_action');
        $pastellProperties->actes_destination = $this->getRecuperateurPost()->get('actes_destination');
        $pastellProperties->actes_send_auto = $this->getRecuperateurPost()->get('actes_send_auto');

        $pastellProperties->helios_flux_id = $this->getRecuperateurPost()->get('helios_flux_id');
        $pastellProperties->helios_action = $this->getRecuperateurPost()->get('helios_action');
        $pastellProperties->helios_destination = $this->getRecuperateurPost()->get('helios_destination');
        $pastellProperties->helios_send_auto = $this->getRecuperateurPost()->get('helios_send_auto');


        $this->getObjectInstancier()->get(PastellPropertiesSQL::class)->editProperties($id,$pastellProperties);
        $this->setErrorMessage("Les informations ont été mises à jour");
        $this->redirect("/admin/authorities/admin_authority_sae.php?id=$id");
    }

}