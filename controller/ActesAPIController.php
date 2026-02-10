<?php

namespace S2lowLegacy\Controller;

use S2low\Exceptions\BadNatureCodeException;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\actes\NaturesActes;
use S2lowLegacy\Model\AuthoritySQL;

class ActesAPIController extends Controller
{
    public function _actionAfter()
    {
        /* Nothing to do*/
    }

    private function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
    }

    public function listStatusAction(): bool
    {
        $this->verifUser();
        $actesStatusSQL = $this->getObjectInstancier()->get(ActesStatusSQL::class);
        $result = $actesStatusSQL->getAllStatus();

        echo json_encode($result);
        return true;
    }

    public function nbActesAction(): bool
    {
        $this->verifUser();

        $status_id = $this->getRecuperateurGet()->getInt('status_id');

        $authority_id = intval($this->me->get('authority_id'));

        $nb_transactions = $this->getActesTransactionsSQL()->getNbByStatusAndAuthority($status_id, $authority_id);

        $result = [
            'status_id' => $status_id,
            'authority_id' => $authority_id,
            'nb_transactions' => $nb_transactions
        ];

        echo json_encode($result);
        return true;
    }

    public function listActesAction(): bool
    {
        $this->verifUser();

        $status_id = $this->getRecuperateurGet()->getInt('status_id');
        $offset = $this->getRecuperateurGet()->getInt('offset');
        $limit = $this->getRecuperateurGet()->getInt('limit', 100);
        $type_acte_string = $this->getRecuperateurGet()->get('type_acte', null);

        $min_submission_date = $this->getRecuperateurGet()->getDate('min_date');
        $max_submission_date = $this->getRecuperateurGet()->getDate('max_date');

        $authority_id = intval($this->me->get('authority_id'));

        try {
            $typeActe = is_null($type_acte_string) ? null : NaturesActes::getFromString($type_acte_string)->value;
        } catch (BadNatureCodeException $e) {
            echo json_encode(legacy_encode_array(['error' => $e->getMessage()]));
        }
        $transactions_list = $this->getActesTransactionsSQL()->getListByStatusAndAuthority(
            $status_id,
            $authority_id,
            $offset,
            $limit,
            $min_submission_date,
            $max_submission_date,
            $typeActe
        );

        $result = [
            'status_id' => $status_id,
            'authority_id' => $authority_id,
            'offset' => $offset,
            'limit' => $limit,
            'transactions' => $transactions_list
        ];

        echo json_encode(legacy_encode_array($result));
        return true;
    }

    public function listDocumentPrefectureAction(): bool
    {
        $this->verifUser();

        $authority_id = intval($this->me->get('authority_id'));
        $list = $this->getActesTransactionsSQL()->listDocumentPrefectureNonLu($authority_id);

        echo json_encode($list);
        return true;
    }

    public function documentPrefectureMarkAsReadAction(): bool
    {
        $this->verifUser();
        $authority_id = intval($this->me->get('authority_id'));
        $transaction_id = $this->getRecuperateurGet()->getInt('transaction_id');
        $this->getActesTransactionsSQL()->markAsRead($authority_id, $transaction_id);
        echo json_encode(['result' => 'ok']);
        return true;
    }

    public function nbCreatedActesByAuthorityGroupIdAndMonthAction(): bool
    {
        $this->verifAdmin();
        $authority_id = intval($this->me->get('authority_id'));
        $authoritySQL = $this->getObjectInstancier()->get(AuthoritySQL::class);
        $authorityInfo = $authoritySQL->getInfo($authority_id);
        $authority_group_id = $authorityInfo['authority_group_id'];

        if ($this->me->isSuper() && $this->getRecuperateurGet()->getInt('authority_group_id')) {
            $authority_group_id = $this->getRecuperateurGet()->getInt('authority_group_id');
        }

        if (! $authority_group_id) {
            echo json_encode(['result' => 'ko', 'message' => 'Authorities is not in a group or no group_id provided']);
            return false;
        }

        $this->verifGroupAdmin($authority_group_id);

        $month = $this->getRecuperateurGet()->getInt('month', date('m', strtotime('last month')));
        $year = $this->getRecuperateurGet()->getInt('year', date('Y', strtotime('last month')));

        $min_date = "$year-$month-01";
        $max_date = date('Y-m-t', strtotime($min_date));

        $nbTransactionPerAuthorities = $this->getActesTransactionsSQL()->getNbActesByAuthorityGroupIdBeetweenDate(
            $authority_group_id,
            $min_date,
            $max_date . 'T23:59:59'
        );
        $result = [
            'result' => 'ok',
            'message' => '',
            'authority_group_id' => $authority_group_id,
            'min_date' => $min_date,
            'max_date' => $max_date,
            'nbTransactionPerAuthorities' => $nbTransactionPerAuthorities
        ];
        echo json_encode($result);
        return true;
    }
}
