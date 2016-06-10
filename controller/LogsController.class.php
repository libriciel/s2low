<?php

class LogsController extends Controller {

	/** @return LogsHistoriqueSQL $logsHistoriqueSQL */
	private function getLogsHistoriqueSQL(){
		return $this->getObjectInstancier()->get('LogsHistoriqueSQL');
	}

	/** @return LogsRequestSQL $logsRequestSQL */
	private function getLogsRequestSQL(){
		return $this->getObjectInstancier()->get('LogsRequestSQL');
	}

	public function viewAction(){
		$recuperateur = $this->getRecuperateurGet();

		$this->fauthority = $recuperateur->get("authority");
		$this->fmodule = $recuperateur->get("module");
		$this->fuser = $recuperateur->get("user");
		$this->fmessage = $recuperateur->get("message");
		$logs_date_min  = $this->getObjectInstancier()->get('LogsSQL')->getMinDate();
		$logs_history_date_max  = $this->getObjectInstancier()->get('LogsHistoriqueSQL')->getMaxDate();

		$date_debut_default = date("Y-m-d",strtotime($logs_date_min));

		$this->date_debut = date("Y-m-d",strtotime($recuperateur->get("date_debut",$date_debut_default)));
		$this->date_fin =  date("Y-m-d",strtotime($recuperateur->get("date_fin",date("Y-m-d"))));

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

		$moduleSQL = new ModuleSQL($this->getSQLQuery());
		$this->module_list = $moduleSQL->getActiveModuleList();

		$logsSQL = new LogsSQL($this->getSQLQuery());
		$this->loglevel_list = $logsSQL->getLogLevelList();

		if ($this->date_debut < $logs_history_date_max){

			if ($this->fauthority) {
				$this->authority_info = $this->getObjectInstancier()->get('AuthoritySQL')->getInfo($this->fauthority);
			}

			$this->fancyDate = new FancyDate();
			$this->has_pending_logs_request =  $this->getLogsRequestSQL()->hasPendingRequest($this->me->get('id'));

			$this->template_milieu = __DIR__."/../template/LogsCreateRequest.php";
			return;
		}


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


		$this->userSQL = new UserSQL($this->getSQLQuery());


		$this->has_logs_request = $this->getLogsRequestSQL()->hasRequest($this->me->get('id'));

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

	public function requestAction(){
		$this->verifUser();


		if ($this->getLogsRequestSQL()->hasPendingRequest($this->me->get('id'))){
			$this->setErrorMessage("Une requête est déjà en cours.");
			$this->redirect("/common/logs_request_view.php");
		}



		$recuperateur = $this->getRecuperateurPost();

		$logsRequestData = new LogsRequestData();

		$logsRequestData->date_debut = $recuperateur->get("date_debut");
		$logsRequestData->date_fin=  $recuperateur->get("date_fin",date("Y-m-d"));
		$logsRequestData->user_id_demandeur = $this->me->get('id');


		if ($this->me->isSuper()){

		} elseif ($this->me->isGroupAdmin()) {
			$logsRequestData->authority_group_id = $this->me->get('authority_group_id');

		} elseif ($this->me->isAuthorityAdmin()) {
			$logsRequestData->authority_id = $this->me->get('authority_id');
		} else {
			$logsRequestData->user_id = $this->me->get('id');
		}

		$this->getLogsRequestSQL()->newRequest($logsRequestData);

		$this->setMessage("Votre demande a été enregistrée.");
		$this->redirect("/common/logs_request_view.php");
	}

	public function requestViewAction(){
		$this->verifUser();
		$this->logs_request_list = $this->getLogsRequestSQL()->getAllByUser($this->me->get('id'));
		$this->fancyDate = new FancyDate();
	}

	public function cancelAction(){
		$recuperateur = $this->getRecuperateurGet();
		$id = $recuperateur->get('id');
		$this->verifUser();
		$this->getLogsRequestSQL()->delete($id,$this->me->get('id'));
		$this->setMessage("La demande a été supprimée");
		$this->redirect("/common/logs_request_view.php");
	}

	public function doRequest(){
		$all_request = $this->getLogsRequestSQL()->getAllByState(LogsRequestData::STATE_ASKING);
		echo count($all_request)." requêtes en attente...";

		foreach($all_request as $request) {

			echo "Traitement de la requête {$request['id']}\n";
			$logsRequestData = new LogsRequestData();
			$logsRequestData->date_debut = $request['date_debut'];
			$logsRequestData->date_fin = $request['date_fin'];
			$logsRequestData->authority_group_id = $request['authority_group_id'];
			$logsRequestData->authority_id = $request['authority_id'];
			$logsRequestData->user_id = $request['user_id'];
			$logsRequestData->user_id_demandeur = $request['user_id_demandeur'];

			$output_filename = EXPORT_LOGS_DIRECTORY . "/{$request['id']}.csv";

			$this->getLogsHistoriqueSQL()->request($logsRequestData, $output_filename);
			$this->getLogsRequestSQL()->setAvailable($request['id']);

			$user_id_demandeur = $request['user_id_demandeur'];

			$user_info = $this->getObjectInstancier()->get('UserSQL')->getInfo($user_id_demandeur);

			mail($user_info['email'],"[S2LOW] Journal disponible",
				"Bonjour,\nVotre fichier contenant les lignes du journal est disponible sur ".
				WEBSITE_SSL."/common/logs_request_view.php\n\nCelui-ci est disponible pendant 24 heures.\n\nCordialement.\n"
			);
		}

		$old_request = $this->getLogsRequestSQL()->getOldRequest();
		foreach($old_request as $request){
			echo "Suppresion de la requête {$request['id']}\n";
			$this->getLogsRequestSQL()->forceDelete($request['id']);
			unlink(EXPORT_LOGS_DIRECTORY."/{$request['id']}.csv");
		}
	}

	public function requestDownloadAction(){
		$recuperateur = $this->getRecuperateurGet();
		$id = $recuperateur->getInt('id');
		$this->verifUser();

		$info = $this->getLogsRequestSQL()->getInfo($id);
		if ($info['user_id_demandeur'] != $this->me->get('id')){
			$this->redirect("/common/logs_request_view.php");
		}

		$filename = "s2low-log-{$id}.csv";

		header("Content-type: text/csv");
		header("Content-disposition: attachment; filename=$filename");

		readfile(EXPORT_LOGS_DIRECTORY."/{$id}.csv");
		exit;

	}


}