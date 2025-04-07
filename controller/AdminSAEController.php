<?php

namespace S2lowLegacy\Controller;

use Exception;
use S2lowLegacy\Class\actes\ActesPrepareSaeWorker;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\helios\HeliosPrepareSaeWorker;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Class\PastellWrapperFactory;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowLegacy\Model\PastellProperties;
use S2lowLegacy\Model\PastellPropertiesSQL;

class AdminSAEController extends Controller
{
    public function _actionBefore($controller, $action)
    {
        $this->verifSuperAdmin();
        parent::_actionBefore($controller, $action);
    }

    public function editAction()
    {
        $recuperateur = $this->getRecuperateurGet();
        $id = $recuperateur->getInt('id');
        $this->id = $id;
        $this->authorityInfo = $this->getObjectInstancier()->get(AuthoritySQL::class)->getInfo($id);
        $this->pastellProperties = $this->getObjectInstancier()->get(PastellPropertiesSQL::class)->getPastellProperties($id); //BUG ??
        $this->title = "SAE - Configuration";
    }

    /**
     * @throws RedirectException
     * @throws Exception
     */
    public function testAction()
    {
        $id = $this->getRecuperateurGet()->getInt('id');

        $pastellProperties = $this->getObjectInstancier()->get(PastellPropertiesSQL::class)->getPastellProperties($id);

        $pastellFactory = $this->getObjectInstancier()->get(PastellWrapperFactory::class);
        $pastell = $pastellFactory->getNewInstance($pastellProperties);
        try {
            $pastell->testConnexion();
            $this->setErrorMessage("Connexion OK");
        } catch (Exception $e) {
            $this->setErrorMessage($e->getMessage());
        }
        $this->redirect("/admin/authorities/admin_authority_sae.php?id=$id");
    }

    /**
     * @throws RedirectException
     */
    public function doEditAction()
    {
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
        $pastellProperties->actes_transaction_id_min = $this->getRecuperateurPost()->getInt('actes_transaction_id_min');
        $pastellProperties->actes_transaction_id_max = $this->getRecuperateurPost()->getInt('actes_transaction_id_max');

        $pastellProperties->helios_flux_id = $this->getRecuperateurPost()->get('helios_flux_id');
        $pastellProperties->helios_action = $this->getRecuperateurPost()->get('helios_action');
        $pastellProperties->helios_destination = $this->getRecuperateurPost()->get('helios_destination');
        $pastellProperties->helios_send_auto = $this->getRecuperateurPost()->get('helios_send_auto');
        $pastellProperties->helios_transaction_id_min = $this->getRecuperateurPost()->getInt('helios_transaction_id_min');
        $pastellProperties->helios_transaction_id_max = $this->getRecuperateurPost()->getInt('helios_transaction_id_max');



        $this->getObjectInstancier()->get(PastellPropertiesSQL::class)->editProperties($id, $pastellProperties);
        $this->setErrorMessage("Les informations ont été mises à jour");
        $this->redirect("/admin/authorities/admin_authority_sae.php?id=$id");
    }

    public function statistiquesAction()
    {
        $authority_id = $this->getRecuperateurGet()->getInt('id');

        $authoritySQL = $this->getObjectInstancier()->get(AuthoritySQL::class);
        $authority_info = $authoritySQL->getInfo($authority_id);

        $pastellPropertiesSQL = $this->getObjectInstancier()->get(PastellPropertiesSQL::class);
        $pastellProperties = $pastellPropertiesSQL->getPastellProperties($authority_id);

        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $this->{'actes_nb_en_retard'} =
            count($actesTransactionsSQL->getTransactionToArchive(
                ActesPrepareSaeWorker::NB_DAYS_ARCHIVE_AFTER,
                $authority_id,
                $pastellProperties->actes_send_auto
            ));
        $this->{'actes_nb_en_attente_transmission_sae'} =
            $actesTransactionsSQL->getNbByStatusAndAuthority(
                ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
                $authority_id
            );

        $this->{'actes_nb_envoye_sae'} =
            $actesTransactionsSQL->getNbByStatusAndAuthority(
                ActesStatusSQL::STATUS_ENVOYE_AU_SAE,
                $authority_id
            );
        $this->{'actes_erreur_lors_de_larchivage'} =
            $actesTransactionsSQL->getNbByStatusAndAuthority(
                ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE,
                $authority_id
            );
        $this->{'actes_erreur_lors_de_lenvoi_sae'} =
            $actesTransactionsSQL->getNbByStatusAndAuthority(
                ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
                $authority_id
            );

        $heliosTransactionsSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);

        $this->{'helios_nb_en_retard'} =
            count($heliosTransactionsSQL->getTransactionToPrepareToSAE(
                HeliosPrepareSaeWorker::NB_DAYS_ARCHIVE_AFTER,
                $authority_id,
                $pastellProperties->helios_send_auto
            ));


        $this->{'helios_nb_en_attente_transmission_sae'} =
            $heliosTransactionsSQL->getNbByStatusAndAuthority(
                HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
                $authority_id
            );
        $this->{'helios_nb_envoye_au_sae'} =
            $heliosTransactionsSQL->getNbByStatusAndAuthority(
                HeliosStatusSQL::ENVOYER_AU_SAE,
                $authority_id
            );
        $this->{'helios_erreur_lors_de_lenvoi_sae'} =
            $heliosTransactionsSQL->getNbByStatusAndAuthority(
                HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
                $authority_id
            );

        $this->{'authority_id'} = $authority_id;
        $this->{'pastellProperties'} = $pastellProperties;
        $this->title = "{$authority_info['name']} - Statistiques SAE";
    }
}
