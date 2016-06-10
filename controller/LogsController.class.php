<?php

class LogsController extends Controller {

	public function viewAction(){
		$recuperateur = $this->getRecuperateurGet();

		$this->fauthority = $recuperateur->get("authority");
		$this->fmodule = $recuperateur->get("module");
		$this->fuser = $recuperateur->get("user");
		$this->fmessage = $recuperateur->get("message");
		$this->date_debut = $recuperateur->get("date_debut");
		$this->date_fin = $recuperateur->get("date_fin");

		$fseverity = Helpers::getVarFromGet("severity");
		if (! isset($fseverity)){
			$fseverity = -1;
		}
		$this->fseverity = intval($fseverity);

		$this->page_number = $recuperateur->getInt('page',1);
		$this->taille_page =  $recuperateur->getInt('count',10);


		$this->verifUser();
		$this->title = "Tedetis : Journal d'évènements";

		$authoritySQL = new AuthoritySQL($this->getSQLQuery());

		$h1_title = "Journal d'évènements";

		$authority_group_id = false;
		$user_id = false;
		$visibility = false;
		$authority_id = false;

		if ($this->me->isSuper()){
			$this->authorities_list = $authoritySQL->getAll();
			$authority_id = $this->fauthority;
		} elseif ($this->me->isGroupAdmin()) {
			$groupSQL = new GroupSQL($this->getSQLQuery());
			$groupe_info = $groupSQL->getInfo($this->me->get("authority_group_id"));
			$h1_title .= " du groupe «&nbsp;{$groupe_info['name']}&nbsp;»";
			$this->authorities_list = $authoritySQL->getAllGroup($this->me->get("authority_group_id"));
			$authority_group_id = $this->me->get("authority_group_id");
			$authority_id = $this->fauthority;
			$visibility = array('GADM','ADM','USER');
		} elseif ($this->me->isAuthorityAdmin()) {
			$authority_info = $authoritySQL->getInfo($this->me->get('authority_id'));
			$h1_title .= " de la collectivité «&nbsp;{$authority_info['name']}&nbsp;»";
			$this->authorities_list = array();
			$authority_id = $this->me->get('authority_id');
			$visibility = array('ADM','USER');
		} else {
			$user_id = $this->me->get('id');
			$visibility = array('USER');
		}
		$this->h1_title = $h1_title;

		$moduleSQL = new ModuleSQL($this->getSQLQuery());
		$this->module_list = $moduleSQL->getActiveModuleList();

		$logsSQL = new LogsSQL($this->getSQLQuery());
		$this->loglevel_list = $logsSQL->getLogLevelList();

		$this->userSQL = new UserSQL($this->getSQLQuery());


		$logsSQL = new LogsSQL($this->getSQLQuery());
		$offset = ($this->page_number - 1) * $this->taille_page;
		$this->logs_list = $logsSQL->getList($authority_group_id,$authority_id,$user_id,$this->fuser,$this->fmodule,$this->fseverity,$this->fmessage,$visibility,$offset,$this->taille_page,$this->date_debut,$this->date_fin);

		$nb_logs = $logsSQL->getNbLog($authority_group_id,$authority_id,$user_id,$this->fuser,$this->fmodule,$this->fseverity,$this->fmessage,$visibility,$this->date_debut,$this->date_fin);

		$pagerHTML  = new PagerHTML();
		$this->side_bar = $pagerHTML->getHTML($this->page_number,$nb_logs,$this->taille_page);;
	}


	public function vidange($nb_month_to_keep){
		/** @var LogsHistoriqueSQL $logsHistoriqueSQL */
		$logsHistoriqueSQL = $this->getObjectInstancier()->get('LogsHistoriqueSQL');
		$logsHistoriqueSQL->vidange($nb_month_to_keep);
	}
	
}