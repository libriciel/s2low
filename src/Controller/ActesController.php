<?php

namespace S2low\Controller;

use Symfony\Component\HttpFoundation\Request;
use S2lowLegacy\Class\actes\TransactionSQL;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Droit;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Model\AuthoritySQL;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/modules/actes')]
class ActesController extends AbstractController
{
    public function __construct(
        private Initialisation $initialisation,
        private Droit $droit,
        private TransactionSQL $transactionSQL,
        private AuthoritySQL $authoritySQL,
    ) {
    }

    #[Route('/index.phpp', name: 'actes_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $authority_filtre =  $request->query->get('authority');
        $fnature =  $request->query->get('nature');
        $ftype =  $request->query->get('type');
        $fnum =  $request->query->get('num');
        $objet = $request->query->get('objet');
        $status =  $request->query->get('status');
        $fmin_submission_date =  $request->query->get('min_submission_date');
        $fmax_submission_date =  $request->query->get('max_submission_date');
        $fmin_ack_date = $request->query->get('min_ack_date');
        $fmax_ack_date =  $request->query->get('max_ack_date');
        $sortWay = $request->query->get('sortway', 'desc');
        $order = $request->query->get('order', 'id');
        $page_number = $request->query->getInt('page', 1);
        $taille_page =  $request->query->getInt('count', 10);

        if (isset($status) && $status === '0') {
            $fstatus = 0;
        } else {
            $fstatus =  $request->query->get('status', 'all');
        }

        if ($fstatus != TransactionSQL::EN_COURS && $fstatus != 'all' && ! is_numeric($fstatus)) {
            $fstatus = TransactionSQL::EN_COURS;
        }

        if ($ftype != '0' && empty($ftype)) {
            $ftype = '1';
        }

        $initData = $this->initialisation->doInit();
        if ($this->droit->isSuperAdmin($initData->userInfo)) {
            $this->transactionSQL->setAuthority($authority_filtre);
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

        $this->transactionSQL->setNature($fnature);
        $this->transactionSQL->setType($ftype);
        $this->transactionSQL->setStatus($fstatus);
        $this->transactionSQL->setNumero($fnum);
        $this->transactionSQL->setDateMinSubmission($fmin_submission_date);
        $this->transactionSQL->setDateMaxSubmission($fmax_submission_date);
        $this->transactionSQL->setDateMinAck($fmin_ack_date);
        $this->transactionSQL->setDateMaxAck($fmax_ack_date);
        $this->transactionSQL->setObjet($objet);
        $this->transactionSQL->setOrder($order, $sortWay);
        $this->transactionSQL->setPageNumber($page_number, $taille_page);

        $enveloppes = $this->transactionSQL->getAll();

        $nb_transactions = $this->transactionSQL->getNbTransaction();

        $transTypes = $this->transactionSQL->getTypes();
        $transTypes['all'] = 'Tous les types';

        $transNatures = $this->transactionSQL->getNatures();
        $status = $this->transactionSQL->getStatus();

        $status[TransactionSQL::EN_COURS] = 'En cours';
        $status['all'] = 'Tous les états';

        return $this->render('actes/index.html.twig', [
            'module_name' => Initialisation::MODULENAMEACTES,
            'nombre_de_page' => ceil($nb_transactions / $taille_page),
            'limit_transaction_par_page' => $taille_page,
            'trans_types' => $transTypes,
            'trans_natures' => $transNatures,
            'status' => $status,
            'fstatus' => $fstatus,
            'fnum' => $fnum,
            'fmin_submission_date' => $fmin_submission_date,
            'fmax_submission_date' => $fmax_submission_date,
            'fmin_ack_date' => $fmin_ack_date,
            'fmax_ack_date' => $fmax_ack_date,
            'allCollectivite' => $this->authoritySQL->getAll(),
            'filtreAuthority' => $authority_filtre,
            'objet' => $objet,
            'enveloppes' => $enveloppes,
        ]);
    }
}
