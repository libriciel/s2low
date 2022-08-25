<?php

use S2lowLegacy\Class\actes\ActesArchiveControler;
use S2lowLegacy\Class\actes\ActesPrepareSaeWorker;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\helios\HeliosPrepareSaeWorker;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class AdminStatsController extends Controller
{
    public function _actionBefore($controller, $action)
    {
        $this->verifSuperAdmin();
        parent::_actionBefore($controller, $action);
    }

    public function statsSAEGlobalAction()
    {

        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $actesArchiveControler = $this->getObjectInstancier()->get(ActesArchiveControler::class);

        $this->{'actes_nb_en_retard'} = count($actesTransactionsSQL->getTransactionToArchive(
            ActesPrepareSaeWorker::NB_DAYS_ARCHIVE_AFTER
        ));
        $this->actes_nb_en_attente_auto = count($actesArchiveControler->getAllTransactionIdToSend());
        $this->actes_nb_envoye_auto = count($actesTransactionsSQL->getTransactionToSendSAE(
            ActesStatusSQL::STATUS_ENVOYE_AU_SAE,
            true
        ));
        $this->actes_erreur_lors_de_larchivage_auto = count($actesTransactionsSQL->getTransactionToSendSAE(
            ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE,
            true
        ));

        $this->actes_erreur_lors_de_lenvoi_sae_auto = count($actesTransactionsSQL->getTransactionToSendSAE(
            ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
            true
        ));




        $this->actes_nb_en_attente_sae_4h = $actesTransactionsSQL->getNbByStatus(ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE);
        $this->actes_nb_envoye_sae_4h = $actesTransactionsSQL->getNbByStatus(ActesStatusSQL::STATUS_ENVOYE_AU_SAE);
        $this->actes_erreur_lors_de_larchivage = $actesTransactionsSQL->getNbByStatus(ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE);
        $this->actes_erreur_lors_de_lenvoi_sae = $actesTransactionsSQL->getNbByStatus(ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE);


        $heliosTransactionsSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);


        $this->helios_nb_en_retard = count($heliosTransactionsSQL->getTransactionToPrepareToSAE(HeliosPrepareSaeWorker::NB_DAYS_ARCHIVE_AFTER));
        $this->helios_nb_en_attente_sae_auto = count($heliosTransactionsSQL->getTransactionToPrepareToSAE(
            HeliosPrepareSaeWorker::NB_DAYS_ARCHIVE_AFTER,
            0,
            true,
            [HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE]
        ));
        $this->helios_nb_envoye_sae_auto  = count(
            $heliosTransactionsSQL->getTransactionToPrepareToSAE(
                HeliosPrepareSaeWorker::NB_DAYS_ARCHIVE_AFTER,
                0,
                true,
                [HeliosStatusSQL::ENVOYER_AU_SAE]
            )
        );
        $this->helios_erreur_lors_de_larchivage_auto  = count(
            $heliosTransactionsSQL->getTransactionToPrepareToSAE(
                HeliosPrepareSaeWorker::NB_DAYS_ARCHIVE_AFTER,
                0,
                true,
                [HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE]
            )
        );




        $this->helios_nb_en_attente_sae_4h = $heliosTransactionsSQL->getNbByStatus(HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE);
        $this->helios_nb_envoye_sae_4h = $heliosTransactionsSQL->getNbByStatus(HeliosStatusSQL::ENVOYER_AU_SAE);
        $this->helios_erreur_lors_de_larchivage = $heliosTransactionsSQL->getNbByStatus(HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE);
    }

    public function SAEActesAction()
    {
        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $this->{'status_list'} = [
            ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
            ActesStatusSQL::STATUS_ENVOYE_AU_SAE,
            ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
            ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE,
        ];
        $this->{'info_list'} = $actesTransactionsSQL->getNbTransactionGroupBySAEStatusForModeAuto($this->{'status_list'});
    }

    public function SAEHeliosAction()
    {
        $heliosTransactionsSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);
        $this->{'status_list'} = [
            HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
            HeliosStatusSQL::ENVOYER_AU_SAE,
            HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
        ];
        $this->{'info_list'} = $heliosTransactionsSQL->getNbTransactionGroupBySAEStatusForModeAuto($this->{'status_list'});
    }
}
