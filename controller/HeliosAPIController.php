<?php

namespace S2lowLegacy\Controller;

use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosAPIController extends Controller
{
    public function _actionAfter()
    {
        /* Nothing to do*/
    }

    private function getHeliosTransactionsSQL()
    {
        return $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);
    }


    public function nbCreatedPesAllerByAuthorityGroupIdAndMonthAction(): bool
    {
        $this->verifAdmin();
        $authority_id = intval($this->userContext->me->get("authority_id"));
        $authoritySQL = $this->getObjectInstancier()->get(AuthoritySQL::class);
        $authorityInfo = $authoritySQL->getInfo($authority_id);
        $authority_group_id = $authorityInfo['authority_group_id'];

        if ($this->userContext->me->isSuper() && $this->getRecuperateurGet()->getInt("authority_group_id")) {
            $authority_group_id = $this->getRecuperateurGet()->getInt("authority_group_id");
        }

        if (! $authority_group_id) {
            echo json_encode(["result" => "ko","message" => "Your authority is not in a group or no group_id provided"]);
            return false;
        }

        $this->verifGroupAdmin($authority_group_id);

        $month = $this->getRecuperateurGet()->getInt('month', date("m", strtotime("last month")));
        $year = $this->getRecuperateurGet()->getInt('year', date("Y", strtotime("last month")));

        $min_date = "$year-$month-01";
        $max_date = date("Y-m-t", strtotime($min_date));

        $nbTransactionPerAuthorities = $this->getHeliosTransactionsSQL()->getNbPesAllerByAuthorityGroupIdBetweenDate(
            $authority_group_id,
            $min_date,
            $max_date . "T23:59:59"
        );
        $result = [
            "result" => "ok",
            "message" => "",
            "authority_group_id" => $authority_group_id,
            "min_date" => $min_date,
            "max_date" => $max_date,
            "nbTransactionPerAuthorities" => $nbTransactionPerAuthorities
        ];
        echo json_encode($result);
        return true;
    }
}
