<?php

namespace S2low\Controller\Legacy;

use S2lowLegacy\Controller\Controller;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HeliosAPIController extends AbstractController
{
    public function __construct(
        HeliosTransactionsSQL $heliosTransactionsSQL,
        AuthoritySQL $authoritySQL,
        LegacyControllerActions $legacyControllerActions
    )
    {
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
        $this->authoritySQL = $authoritySQL;
        $this->legacyControllerActions = $legacyControllerActions;
    }

    public function _actionAfter()
    {
        /* Nothing to do*/
    }

    private function getHeliosTransactionsSQL()
    {
        return $this->heliosTransactionsSQL;
    }


    /**
     * @Route("/modules/helios/api/nb_pes_aller_by_authorities_and_date.php", name="app_modules_helios_api_nb_pes_aller_by_authorities_and_date")
     * @return bool
     */
    public function nbCreatedPesAllerByAuthorityGroupIdAndMonthAction(): bool
    {
        $this->verifAdmin();
        $authority_id = intval($this->me->get("authority_id"));
        $authorityInfo = $this->authoritySQL->getInfo($authority_id);
        $authority_group_id = $authorityInfo['authority_group_id'];

        if ($this->me->isSuper() && $this->getRecuperateurGet()->getInt("authority_group_id")) {
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
