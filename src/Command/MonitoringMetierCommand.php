<?php

namespace S2low\Command;

use S2low\Services\Helios\HeliosEnvoiWorkerFactory;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\BeanstalkdWrapper;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MonitoringMetierCommand extends Command
{
    public function __construct(
        private readonly ActesTransactionsSQL $actesTransactionsSQL,
        private readonly HeliosTransactionsSQL $heliosTransactionsSQL,
        private readonly BeanstalkdWrapper $beanstalkdWrapper,
        private readonly HeliosEnvoiWorkerFactory $heliosEnvoiWorkerFactory
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('stats:monitoring-metier')
            ->setDescription(
                "Génère une extraction des données métiers utiles pour le monitoring"
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $date_status_cible = date("c", strtotime("now -1 minutes"));

        $nbPostesParMin = $this->actesTransactionsSQL->getNbPostesDepuis($date_status_cible);

        list($nbEnAttenteParMin,$delaiPosteEnAttente) =
            $this->actesTransactionsSQL->getStatusTransitionStatistics(
                $date_status_cible,
                ActesStatusSQL::STATUS_POSTE,
                ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION
            );
        list($nbTransmisParMin,$delaiEnAttenteTransmis) = $this->actesTransactionsSQL->getStatusTransitionStatistics(
            $date_status_cible,
            ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION,
            ActesStatusSQL::STATUS_TRANSMIS
        );
        list($nbAcquittementParMin,$delaiTransmisAcquittement) = $this->actesTransactionsSQL->getStatusTransitionStatistics(
            $date_status_cible,
            ActesStatusSQL::STATUS_TRANSMIS,
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU
        );


        list($nbHeliosPostesPar15Min,$volumePoste) = $this->heliosTransactionsSQL->getNbPostesDepuis($date_status_cible);

        list($nbHeliosEnAttentePar15Min,$delaiHeliosPosteEnAttente,$volumeEnAttente) = $this->heliosTransactionsSQL->getStatusTransitionStatistics(
            $date_status_cible,
            HeliosTransactionsSQL::POSTE,
            HeliosTransactionsSQL::ATTENTE
        );


        list($nbHeliosTransmisPar15Min,$delaiHeliosEnAttenteTransmis,$volumeTransmis) = $this->heliosTransactionsSQL->getStatusTransitionStatistics(
            $date_status_cible,
            HeliosTransactionsSQL::ATTENTE,
            HeliosTransactionsSQL::TRANSMIS
        );

        list($nbHeliosAcquittementPar15Min,$delaiHeliosTransmisAcquittement,$volumeAcquite) = $this->heliosTransactionsSQL->getStatusTransitionStatistics(
            $date_status_cible,
            HeliosTransactionsSQL::TRANSMIS,
            HeliosTransactionsSQL::INFORMATION_DISPONIBLE
        );

        list($nbHeliosPostesPar15Min_pt,$volumePoste_pt) =
            $this->heliosTransactionsSQL->getNbPostesDepuis($date_status_cible, true);

        list(
            $nbHeliosEnAttentePar15Min_pt,
            $delaiHeliosPosteEnAttente_pt,
            $volumeEnAttente_pt
            ) = $this->heliosTransactionsSQL->getStatusTransitionStatistics(
                $date_status_cible,
                HeliosTransactionsSQL::POSTE,
                HeliosTransactionsSQL::ATTENTE,
                true
            );


        list(
            $nbHeliosTransmisPar15Min_pt,
            $delaiHeliosEnAttenteTransmis_pt,
            $volumeTransmis_helios_pt
            ) = $this->heliosTransactionsSQL->getStatusTransitionStatistics(
                $date_status_cible,
                HeliosTransactionsSQL::ATTENTE,
                HeliosTransactionsSQL::TRANSMIS,
                true
            );

        list(
            $nbHeliosAcquittementPar15Min_pt,
            $delaiHeliosTransmisAcquittement_pt,
            $volumeAcquite_pt
            ) = $this->heliosTransactionsSQL->getStatusTransitionStatistics(
                $date_status_cible,
                HeliosTransactionsSQL::TRANSMIS,
                HeliosTransactionsSQL::INFORMATION_DISPONIBLE,
                true
            );

        $resultats = [
            "nbPostesParMin" => $nbPostesParMin,
            "nbEnAttenteParMin" => $nbEnAttenteParMin,
            "nbTransmisParMin" => $nbTransmisParMin,
            "nbAcquittesParMin" => $nbAcquittementParMin,
            "delaiPosteEnAttente" => $delaiPosteEnAttente,
            "delaiEnAttenteTransmis" => $delaiEnAttenteTransmis,
            "delaiTransmisAcquittement" => $delaiTransmisAcquittement,
            "nbHeliosPostesParMin" => $nbHeliosPostesPar15Min,

            "nbHeliosEnAttenteParMin" => $nbHeliosEnAttentePar15Min,
            "nbHeliosTransmisParMin" => $nbHeliosTransmisPar15Min,
            "nbHeliosAcquittesParMin" => $nbHeliosAcquittementPar15Min,
            "volumePosteParMin" => $volumePoste,
            "volumeEnAttenteParMin" => $volumeEnAttente,
            "volumeTransmisParMin" => $volumeTransmis,

            "delaiHeliosPosteEnAttente" => $delaiHeliosPosteEnAttente,
            "delaiHeliosEnAttenteTransmis" => $delaiHeliosEnAttenteTransmis,
            "delaiHeliosTransmisAcquittement" => $delaiHeliosTransmisAcquittement,

            "nbHeliosEnAttenteParMin_pt" => $nbHeliosEnAttentePar15Min_pt,
            "nbHeliosTransmisParMin_pt" => $nbHeliosTransmisPar15Min_pt,
            "nbHeliosAcquittesParMin_pt" => $nbHeliosAcquittementPar15Min_pt,
            "volumePosteParMin_pt" => $volumePoste_pt,
            "volumeEnAttenteParMin_pt" => $volumeEnAttente_pt,
            "volumeTransmisParMin_pt" => $volumeTransmis_helios_pt,

            "delaiHeliosPosteEnAttente_pt" => $delaiHeliosPosteEnAttente_pt,
            "delaiHeliosEnAttenteTransmis_pt" => $delaiHeliosEnAttenteTransmis_pt,
            "delaiHeliosTransmisAcquittement_pt" => $delaiHeliosTransmisAcquittement_pt,
        ];

        $tubeStats = $this->beanstalkdWrapper->getQueue('helios-envoi')->statsTube('helios-envoi');

        $resultats['current-jobs-urgent'] = (int) $tubeStats['current-jobs-urgent'];
        $resultats['current-jobs-ready'] = (int) $tubeStats['current-jobs-ready'];
        $resultats['current-jobs-reserved'] = (int) $tubeStats['current-jobs-reserved'];
        $resultats['current-jobs-delayed'] = (int) $tubeStats['current-jobs-delayed'];
        $resultats['current-jobs-buried'] = (int) $tubeStats['current-jobs-buried'];
        $resultats['total-jobs'] = (int) $tubeStats['total-jobs'];
        $resultats['current-using'] = (int)$tubeStats['current-using'];
        $resultats['current-watching'] = (int) $tubeStats['current-watching'];
        $resultats['current-waiting'] = (int) $tubeStats['current-waiting'];

        $heliosEnvoiWorkerGateway = $this->heliosEnvoiWorkerFactory->get(false);
        $nbIdToSendGateway = count($heliosEnvoiWorkerGateway->getAllId());

        $resultats['nbIdToSendGateway'] = $nbIdToSendGateway;

        $tubeStatsPT = $this->beanstalkdWrapper->getQueue('helios-envoi-passtrans')
            ->statsTube('helios-envoi-passtrans');

        $resultats['current-jobs-urgent-passtrans'] = (int) $tubeStatsPT['current-jobs-urgent'];
        $resultats['current-jobs-ready-passtrans'] = (int) $tubeStatsPT['current-jobs-ready'];
        $resultats['current-jobs-reserved-passtrans'] = (int) $tubeStatsPT['current-jobs-reserved'];
        $resultats['current-jobs-delayed-passtrans'] = (int) $tubeStatsPT['current-jobs-delayed'];
        $resultats['current-jobs-buried-passtrans'] = (int) $tubeStatsPT['current-jobs-buried'];
        $resultats['total-jobs-passtrans'] = (int) $tubeStatsPT['total-jobs'];
        $resultats['current-using-passtrans'] = (int)$tubeStatsPT['current-using'];
        $resultats['current-watching-passtrans'] = (int) $tubeStatsPT['current-watching'];
        $resultats['current-waiting-passtrans'] = (int) $tubeStatsPT['current-waiting'];

        $heliosEnvoiWorkerPasstrans = $this->heliosEnvoiWorkerFactory->get(true);
        $nbIdToSendGateway = count($heliosEnvoiWorkerPasstrans->getAllId());

        $resultats['nbIdToSendPasstrans'] = $nbIdToSendGateway;

        echo json_encode($resultats);
        return 0;
    }
}
