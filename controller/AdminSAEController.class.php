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
		$this->title = "SAE - Configuration";
	}

}