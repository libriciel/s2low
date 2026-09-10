<?php

namespace S2lowLegacy\Controller;

use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesIncludedFileSQL;
use S2lowLegacy\Class\CSVOutput;
use S2lowLegacy\Model\AuthoritySQL;

class ActesExportController extends Controller
{
    protected string $date_debut;
    protected string $date_fin;
    protected string $authority_id;
    protected array $authority_id_list;
    public const MAX_EXPORT_INTERVAL_IN_DAY = 400;

    public function indexAction()
    {
        $this->verifAdmin();
        $this->setViewParameter('title', "Actes - Export des informations");
        $authoritySQL = $this->getObjectInstancier()->get(AuthoritySQL::class);

        $date_debut = $this->getRecuperateurGet()->get('date_debut');
        $date_fin = $this->getRecuperateurGet()->get('date_fin');
        $this->authority_id = $this->getRecuperateurGet()->get('authority_id');

        if ($this->me->isSuper()) {
            $this->authority_id_list = $authoritySQL->getAll();
        } elseif ($this->me->isGroupAdmin()) {
            $this->authority_id_list = $authoritySQL->getAllAdministeredBy((int)$this->me->get('authority_group_id'));
        } else {
            $this->authority_id = $this->me->get('authority_id');
        }

        $this->date_debut = $date_debut ?: date("Y-m-d", strtotime("-1 month"));
        $this->date_fin =  $date_fin ?: date("Y-m-d");
    }

    public function handlerAction()
    {
        $this->verifAdmin();
        $date_debut = $this->getRecuperateurGet()->get('date_debut');
        $date_fin = $this->getRecuperateurGet()->get('date_fin');
        $authority_id = $this->getRecuperateurGet()->get('authority_id');

        if (strtotime($date_fin) - strtotime($date_debut) > self::MAX_EXPORT_INTERVAL_IN_DAY * 86440) {
            $this->redirect(
                "/modules/actes/actes_export.php?date_debut=$date_debut&date_fin=$date_fin&authority_id=$authority_id",
                sprintf(
                    "La récupération est limitée à un intervalle de %d jours",
                    self::MAX_EXPORT_INTERVAL_IN_DAY
                )
            );
        }
        $authority_group_id = false;

        if ($this->me->isGroupAdmin()) {
            if ($authority_id) {
                $this->verifAdmin($authority_id);
            } else {
                $authority_group_id = $this->me->get('authority_group_id');
            }
        }
        if ($this->me->isAuthorityAdmin()) {
            $authority_id = $this->me->get('authority_id');
        }

        $result = array();

        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);
        $envelope_list = $actesEnvelopeSQL->listEnveloppe($date_debut, $date_fin, $authority_id, $authority_group_id);
        foreach ($envelope_list as $envelope_info) {
            $line = array(
                $envelope_info['id'],
                $envelope_info['submission_date'],
                basename($envelope_info['file_path']),
                implode("\n", $this->getObjectInstancier()->get(ActesIncludedFileSQL::class)->getAllFilenameInEnvelope($envelope_info['id'])),
                strval($envelope_info['siren']),
                strval($envelope_info['department']),
                strval($envelope_info['district']),
                $envelope_info['name'],
                $envelope_info['telephone'],
                $envelope_info['email'],
            );

            $result[] = $line;
        }

        $csvOutput = $this->getObjectInstancier()->get(CSVOutput::class);
        $csvOutput->sendAttachment("actes.csv", $result);

        $this->controller_exit();
    }
}
