<?php

namespace S2low\Controller;

use S2low\DTO\ActesSearchCriteria;
use S2lowLegacy\Class\actes\TransactionSQL;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Droit;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Model\AuthoritySQL;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/modules/actes')]
class ActesController extends AbstractController
{
    public function __construct(
        private readonly Initialisation $initialisation,
        private readonly Droit $droit,
        private readonly TransactionSQL $transactionSQL,
        private readonly AuthoritySQL $authoritySQL,
    ) {
    }

    #[Route('/index.php', name: 'actes_index', methods: ['GET'])]
    public function index(
        #[MapQueryString] ActesSearchCriteria $criteria = new ActesSearchCriteria(),
    ): Response {

        $initData = $this->initialisation->doInit();
        if ($this->droit->isSuperAdmin($initData->userInfo)) {
            $this->transactionSQL->setAuthority($criteria->authority);
        } elseif ($this->droit->isAdmin($initData->userInfo)) {
            $this->transactionSQL->setAuthority($initData->userInfo['authority_id']);
        } else {
            $serviceUser = new ServiceUser(DatabasePool::getInstance());
            $collegues = $serviceUser->getMesCollegues($initData->connexion->getId());
            $collegue[] = $initData->connexion->getId();
            foreach ($collegues as $info) {
                $collegue[] =  $info['id_user'];
            }
            $this->transactionSQL->setUserId($collegue);
        }
        $this->transactionSQL->setNature($criteria->nature);
        $this->transactionSQL->setType($criteria->type);
        $this->transactionSQL->setStatus($criteria->status->value);
        $this->transactionSQL->setNumero($criteria->num);
        $this->transactionSQL->setDateMinSubmission($criteria->min_submission_date);
        $this->transactionSQL->setDateMaxSubmission($criteria->max_submission_date);
        $this->transactionSQL->setDateMinAck($criteria->min_ack_date);
        $this->transactionSQL->setDateMaxAck($criteria->max_ack_date);
        $this->transactionSQL->setObjet($criteria->objet);
        $this->transactionSQL->setOrder($criteria->order, $criteria->sortway);
        $this->transactionSQL->setPageNumber($criteria->page, $criteria->count);

        $enveloppes = $this->transactionSQL->getAll();

        $nb_transactions = $this->transactionSQL->getNbTransaction();

        $transTypes = $this->transactionSQL->getTypes();
        $transTypes[] = 'Tous les types';

        $transNatures = $this->transactionSQL->getNatures();

        $status = $this->transactionSQL->getStatus();
        $status[TransactionSQL::EN_COURS] = 'En cours';
        $status[] = 'Tous les états';

        return $this->render('actes/index.html.twig', [
            'module_name' => Initialisation::MODULENAMEACTES,
            'nombre_de_pages' => ceil($nb_transactions / $criteria->count),
            'limit_transaction_par_page' => $criteria->count,
            'trans_types' => $transTypes,
            'trans_natures' => $transNatures,
            'status' => $status,
            'ftype' => $criteria->type,
            'fnature' => $criteria->nature,
            'fstatus' => $criteria->status->value,
            'fnum' => $criteria->num,
            'fmin_submission_date' => $criteria->min_submission_date,
            'fmax_submission_date' => $criteria->max_submission_date,
            'fmin_ack_date' => $criteria->min_ack_date,
            'fmax_ack_date' => $criteria->max_ack_date,
            'datepicker_min_submission_date' => $criteria->datepicker_min_submission_date,
            'datepicker_max_submission_date' => $criteria->datepicker_max_submission_date,
            'datepicker_min_ack_date' => $criteria->datepicker_min_ack_date,
            'datepicker_max_ack_date' => $criteria->datepicker_max_ack_date,
            'allCollectivite' => $this->authoritySQL->getAll(),
            'filtreAuthority' => $criteria->authority,
            'objet' => $criteria->objet,
            'enveloppes' => $enveloppes,
            'nb_transactions' => $nb_transactions,
        ]);
    }
}
