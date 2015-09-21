<?php

class AdminController extends Controller {
	
	public function _actionBefore($controller,$action){
		parent::_actionBefore($controller,$action);
	}
		
	public function authoritySiretAction(){
		$recuperateur = $this->getRecuperateurGet();
		$id = $recuperateur->getInt('id');
		$this->siret = $recuperateur->get('siret');
		
		$authoritySQL = new AuthoritySQL($this->getSQLQuery());
		$this->authority_info = $authoritySQL->getInfo($id);
		if (! $this->authority_info){
			$this->setErrorMessage("Aucune collectivité trouvée");
			$this->redirectSSL("/admin/authorities/admin_authorities.php");
		} // @codeCoverageIgnore
		
		$this->verifAdmin($id);
		
		$this->authority_id = $id;
		$authoritySiret = new AuthoritySiretSQL($this->getSQLQuery());
		$this->siret_list = $authoritySiret->siretList($id);
		
		$this->siret_exemple = $this->getSiret()->generate();
		
		$this->title = "Numéros SIRET - {$this->authority_info['name']}";
	}
	
	public function authoritySiretAddAction(){
		$this->verifSuperAdmin();
		$recuperateur = $this->getRecuperateurPost();
		$authority_id = $recuperateur->getInt('authority_id');
		$siret = $recuperateur->get('siret');
		
		$authoritySQL = new AuthoritySQL($this->getSQLQuery());
		$this->authority_info = $authoritySQL->getInfo($authority_id);
		if (! $this->authority_info){
			$this->setErrorMessage("Aucune collectivité trouvée");
			$this->redirectSSL("/admin/authorities/admin_authorities.php");
		} // @codeCoverageIgnore
		
		
		if (! $this->getSiret()->isValid($siret)){
			$this->setErrorMessage("Le numéro SIRET n'est pas valide");
			$this->redirectSSL("/admin/authorities/admin_authority_siret.php?id=$authority_id&siret=$siret");
		} // @codeCoverageIgnore
		
		$authoritySiret = new AuthoritySiretSQL($this->getSQLQuery());
		$authoritySiret->add($authority_id, $siret);		
		$this->redirectSSL("/admin/authorities/admin_authority_siret.php?id=$authority_id");
	} // @codeCoverageIgnore
	
	public function authoritySiretDelAction(){
		$this->verifSuperAdmin();
		$recuperateur = $this->getRecuperateurPost();
		$authority_siret_id = $recuperateur->getInt('authority_siret_id');
		$authoritySiret = new AuthoritySiretSQL($this->getSQLQuery());
		$info = $authoritySiret->getInfo($authority_siret_id);
		$authoritySiret->del($authority_siret_id);
		$this->redirectSSL("/admin/authorities/admin_authority_siret.php?id={$info['authority_id']}&siret={$info['siret']}");
	} // @codeCoverageIgnore
	
	/**
	 * @return Siret
	 */
	private function getSiret(){
		return $this->getObjectInstancier()->Siret;
	}
	
	
	
}