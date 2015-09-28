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
	
	public function authoritiesAction(){
		$this->verifAdmin();
		$pagerHTML  = new PagerHTML();
		$recuperateur = $this->getRecuperateurGet();

		$this->ftype =  $recuperateur->get("type");
		$this->fname = $recuperateur->get("name");
		$this->fgroup = $recuperateur->get("group");
		$this->api = $recuperateur->get("api");
		$this->fsiren = $recuperateur->get("siren");
		$this->fsiret = $recuperateur->get("siret");
		$this->count = $recuperateur->get("count")?:10;
		$this->page_number = $recuperateur->getInt('page',1);
		$this->taille_page =  $recuperateur->getInt('count',10);


		$authoritySQL = new AuthoritySQL($this->getSQLQuery());
		$this->authorities = $authoritySQL->getList($this->fgroup, $this->ftype,$this->fname,$this->fsiren,$this->fsiret,($this->page_number - 1) * $this->taille_page,$this->taille_page);

		$nb_authorities = $authoritySQL->getNb($this->fgroup, $this->ftype,$this->fname,$this->fsiren,$this->fsiret);

		if ($this->api){
			$jsonOutput = new JSONoutput();
			$jsonOutput->retrictAndDisplay($this->authorities,array('id','name','authority_group_id','siren','address','city','postal_code','telephone'));
			exit;
		}

		$authorityTypes = new AuthorityTypesSQL($this->getSQLQuery());
		$this->authority_types = $authorityTypes->getChildList();

		$this->side_bar = $pagerHTML->getHTML($this->page_number,$nb_authorities,$this->taille_page);;

		if ($this->me->isGroupAdmin()){
			$userSQL = new UserSQL();
			$group_name = $userSQL->getGroupeName($this->me->getId());
			$this->titre = "Gestion des collectivités du groupe $group_name";
			$this->groupe_list = false;
		} else {
			$this->titre = "Gestion des collectivités";
			$groupeSQL = new GroupSQL($this->getSQLQuery());
			$this->groupe_list = $groupeSQL->getAll();
		}

	}


	
}