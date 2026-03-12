<?php

namespace S2lowLegacy\Controller;

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\PagerHTML;
use S2lowLegacy\Lib\FancyDate;
use S2lowLegacy\Lib\SigTermHandler;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\GroupSQL;
use S2lowLegacy\Model\LogsHistoriqueSQL;
use S2lowLegacy\Model\LogsRequestData;
use S2lowLegacy\Model\LogsRequestSQL;
use S2lowLegacy\Model\LogsSQL;
use S2lowLegacy\Model\ModuleSQL;
use S2lowLegacy\Model\UserSQL;

class LogsController extends Controller
{
    public string $fauthority;
    public string $fmodule;
    public string $fuser;
    public string $fmessage;
    public string|false $date_debut;
    public string|false $date_fin;
    public int $fseverity;
    public int $page_number;
    public int $taille_page;
    public string $title;
    public array $module_list;
    public array $loglevel_list;
    public array $authority_info;
    public FancyDate $fancyDate;
    public bool $has_pending_logs_request;
    public string $template_milieu;
    public array $authorities_list;
    public string $h1_title;
    public UserSQL $userSQL;
    public bool $has_logs_request;
    public array $logs_list;
    public string $side_bar;
    public array $logs_request_list;

    /** @return LogsHistoriqueSQL $logsHistoriqueSQL */
    private function getLogsHistoriqueSQL()
    {
        return $this->getObjectInstancier()->get(LogsHistoriqueSQL::class);
    }

    /** @return LogsRequestSQL $logsRequestSQL */
    private function getLogsRequestSQL()
    {
        return $this->getObjectInstancier()->get(LogsRequestSQL::class);
    }

    public function viewAction()
    {

        $recuperateur = $this->getRecuperateurGet();

        $this->fauthority = $recuperateur->get("authority");
        $this->fmodule = $recuperateur->get("module");
        $this->fuser = $recuperateur->get("user");
        $this->fmessage = $recuperateur->get("message");
        $logs_date_min  = $this->getObjectInstancier()->get(LogsSQL::class)->getMinDate();
        $logs_history_date_max  = $this->getObjectInstancier()->get(LogsHistoriqueSQL::class)->getMaxDate();

        $timestamp_max = 0; //Quickfix php 8
        if (!is_null($logs_history_date_max)) {
            $timestamp_max = strtotime($logs_history_date_max);
        }
        $logs_history_date_max =  date("Y-m-d", $timestamp_max);

        $timestamp_min = null;

        if (!is_null($logs_date_min)) {
            $timestamp_min = strtotime($logs_date_min);
        }
        $date_debut_default = date("Y-m-d", $timestamp_min);

        $this->date_debut = date("Y-m-d", strtotime($recuperateur->get("date_debut", $date_debut_default)));
        $this->date_fin =  date("Y-m-d", strtotime($recuperateur->get("date_fin", date("Y-m-d"))));

        $fseverity = Helpers::getVarFromGet("severity");
        if (! isset($fseverity)) {
            $fseverity = -1;
        }
        $this->fseverity = intval($fseverity);

        $this->page_number = $recuperateur->getInt('page', 1);
        $this->taille_page =  $recuperateur->getInt('count', 10);

        $this->verifUser();
        $this->setViewParameter('title', "Tedetis : Journal d'évènements");

        $authoritySQL = new AuthoritySQL($this->getSQLQuery());

        $h1_title = "Journal d'évènements";

        $moduleSQL = new ModuleSQL($this->getSQLQuery());
        $this->module_list = $moduleSQL->getActiveModuleList();

        $logsSQL = new LogsSQL($this->getSQLQuery());
        $this->loglevel_list = $logsSQL->getLogLevelList();

        if ($this->date_debut < $logs_history_date_max) {
            if ($this->fauthority) {
                $this->authority_info = $this->getObjectInstancier()->get(AuthoritySQL::class)->getInfo($this->fauthority);
            }

            $this->fancyDate = new FancyDate();
            $this->has_pending_logs_request =  $this->getLogsRequestSQL()->hasPendingRequest($this->userContext->me->get('id'));

            $this->setViewParameter('template_milieu', __DIR__ . "/../template/LogsCreateRequest.php");
            return;
        }


        $authority_group_id = false;
        $user_id = false;
        $visibility = false;
        $authority_id = false;

        if ($this->userContext->me->isSuper()) {
            $this->authorities_list = $authoritySQL->getAll();
            $authority_id = $this->fauthority;
        } elseif ($this->userContext->me->isGroupAdmin()) {
            $groupSQL = new GroupSQL($this->getSQLQuery());
            $groupe_info = $groupSQL->getInfo($this->userContext->me->get("authority_group_id"));
            $h1_title .= " du groupe «&nbsp;{$groupe_info['name']}&nbsp;»";
            $this->authorities_list = $authoritySQL->getAllGroup($this->userContext->me->get("authority_group_id"));
            $authority_group_id = $this->userContext->me->get("authority_group_id");
            $authority_id = $this->fauthority;
            $visibility = array('GADM','ADM','USER');
        } elseif ($this->userContext->me->isAuthorityAdmin()) {
            $authority_info = $authoritySQL->getInfo($this->userContext->me->get('authority_id'));
            $h1_title .= " de la collectivité «&nbsp;{$authority_info['name']}&nbsp;»";
            $this->authorities_list = array();
            $authority_id = $this->userContext->me->get('authority_id');
            $visibility = array('ADM','USER');
        } else {
            $user_id = $this->userContext->me->get('id');
            $visibility = array('USER');
        }
        $this->h1_title = $h1_title;


        $this->userSQL = new UserSQL($this->getSQLQuery());


        $this->has_logs_request = $this->getLogsRequestSQL()->hasRequest($this->userContext->me->get('id'));

        $logsSQL = new LogsSQL($this->getSQLQuery());
        $offset = ($this->page_number - 1) * $this->taille_page;
        $this->logs_list = $logsSQL->getList($authority_group_id, $authority_id, $user_id, $this->fuser, $this->fmodule, $this->fseverity, $this->fmessage, $visibility, $offset, $this->taille_page, $this->date_debut, $this->date_fin);

        if ($this->userContext->me->isSuper()) {
            $nb_logs = ($this->page_number + 10) * $this->taille_page;
        } else {
            $nb_logs = $logsSQL->getNbLog($authority_group_id, $authority_id, $user_id, $this->fuser, $this->fmodule, $this->fseverity, $this->fmessage, $visibility, $this->date_debut, $this->date_fin);
        }

        $pagerHTML  = new PagerHTML();
        $this->setViewParameter('side_bar', $pagerHTML->getHTML($this->page_number, $nb_logs, $this->taille_page));
    }


    public function vidange($nb_month_to_keep)
    {
        /** @var LogsHistoriqueSQL $logsHistoriqueSQL */
        $logsHistoriqueSQL = $this->getObjectInstancier()->get(LogsHistoriqueSQL::class);
        $logsHistoriqueSQL->vidange($nb_month_to_keep);
    }

    public function requestAction()
    {
        $this->verifUser();


        if ($this->getLogsRequestSQL()->hasPendingRequest($this->userContext->me->get('id'))) {
            $this->setErrorMessage("Une requête est déjà en cours.");
            $this->redirect("/common/logs_request_view.php");
        }



        $recuperateur = $this->getRecuperateurPost();

        $logsRequestData = new LogsRequestData();

        $logsRequestData->date_debut = $recuperateur->get("date_debut");
        $logsRequestData->date_fin =  $recuperateur->get("date_fin", date("Y-m-d"));
        $logsRequestData->user_id_demandeur = $this->userContext->me->get('id');


        if ($this->userContext->me->isSuper()) {
        } elseif ($this->userContext->me->isGroupAdmin()) {
            $logsRequestData->authority_group_id = $this->userContext->me->get('authority_group_id');
        } elseif ($this->userContext->me->isAuthorityAdmin()) {
            $logsRequestData->authority_id = $this->userContext->me->get('authority_id');
        } else {
            $logsRequestData->user_id = $this->userContext->me->get('id');
        }

        $this->getLogsRequestSQL()->newRequest($logsRequestData);

        $this->setMessage("Votre demande a été enregistrée.");
        $this->redirect("/common/logs_request_view.php");
    }

    public function requestViewAction()
    {
        $this->verifUser();
        $this->logs_request_list = $this->getLogsRequestSQL()->getAllByUser($this->userContext->me->get('id'));
        $this->fancyDate = new FancyDate();
    }

    public function cancelAction()
    {
        $recuperateur = $this->getRecuperateurGet();
        $id = $recuperateur->get('id');
        $this->verifUser();
        $this->getLogsRequestSQL()->delete($id, $this->userContext->me->get('id'));
        $this->setMessage("La demande a été supprimée");
        $this->redirect("/common/logs_request_view.php");
    }

    public function requestDownloadAction()
    {
        $recuperateur = $this->getRecuperateurGet();
        $id = $recuperateur->getInt('id');
        $this->verifUser();

        $info = $this->getLogsRequestSQL()->getInfo($id);
        if ($info['user_id_demandeur'] != $this->userContext->me->get('id')) {
            $this->redirect("/common/logs_request_view.php");
        }

        $filename = "s2low-log-{$id}.csv";

        header("Content-type: text/csv");
        header("Content-disposition: attachment; filename=$filename");

        readfile(EXPORT_LOGS_DIRECTORY . "/{$id}.csv");
        exit;
    }
}
