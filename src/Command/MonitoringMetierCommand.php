<?php

namespace S2low\Command;

use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MonitoringMetierCommand extends Command
{
    /**
     * @var \S2lowLegacy\Class\actes\ActesTransactionsSQL
     */
    private ActesTransactionsSQL $actesTransactionsSQL;
    /**
     * @var \S2lowLegacy\Model\HeliosTransactionsSQL
     */
    private HeliosTransactionsSQL $heliosTransactionsSQL;

    public function __construct(ActesTransactionsSQL $actesTransactionsSQL, HeliosTransactionsSQL $heliosTransactionsSQL)
    {
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('stats:monitoring-metier')
            ->setDescription(
                "Génère une extraction des données métiers utiles pour le monitoring"
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $date_status_cible = date("c", strtotime("now -15 minutes"));


        $nbPostesParMin = $this->actesTransactionsSQL->getNbPostesDepuis($date_status_cible);
        list($nbEnAttenteParMin,$delaiPosteEnAttente) = $this->actesTransactionsSQL->getStatusTransitionStatistics(
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


        $nbHeliosPostesPar15Min = $this->heliosTransactionsSQL->getNbPostesDepuis($date_status_cible);
        list($nbHeliosEnAttentePar15Min,$delaiHeliosPosteEnAttente) = $this->heliosTransactionsSQL->getStatusTransitionStatistics(
            $date_status_cible,
            HeliosTransactionsSQL::POSTE,
            HeliosTransactionsSQL::ATTENTE
        );
        list($nbHeliosTransmisPar15Min,$delaiHeliosEnAttenteTransmis) = $this->heliosTransactionsSQL->getStatusTransitionStatistics(
            $date_status_cible,
            HeliosTransactionsSQL::ATTENTE,
            HeliosTransactionsSQL::TRANSMIS
        );
        list($nbHeliosAcquittementPar15Min,$delaiHeliosTransmisAcquittement) = $this->heliosTransactionsSQL->getStatusTransitionStatistics(
            $date_status_cible,
            HeliosTransactionsSQL::TRANSMIS,
            HeliosTransactionsSQL::INFORMATION_DISPONIBLE
        );

        $resultats = [
            "nbPostesParMin" => $nbPostesParMin / 15,
            "nbEnAttenteParMin" => $nbEnAttenteParMin / 15,
            "nbTransmisParMin" => $nbTransmisParMin / 15,
            "nbAcquittesParMin" => $nbAcquittementParMin / 15,
            "delaiPosteEnAttente" => $delaiPosteEnAttente,
            "delaiEnAttenteTransmis" => $delaiEnAttenteTransmis,
            "delaiTransmisAcquittement" => $delaiTransmisAcquittement,
            "nbHeliosPostesParMin" => $nbHeliosPostesPar15Min / 15,
            "nbHeliosEnAttenteParMin" => $nbHeliosEnAttentePar15Min / 15,
            "nbHeliosTransmisParMin" => $nbHeliosTransmisPar15Min / 15,
            "nbHeliosAcquittesParMin" => $nbHeliosAcquittementPar15Min / 15,
            "delaiHeliosPosteEnAttente" => $delaiHeliosPosteEnAttente,
            "delaiHeliosEnAttenteTransmis" => $delaiHeliosEnAttenteTransmis,
            "delaiHeliosTransmisAcquittement" => $delaiHeliosTransmisAcquittement,
        ];

        echo json_encode($resultats);
        return 0;
    }
}
