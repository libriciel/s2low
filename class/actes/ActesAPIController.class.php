<?php

class ActesAPIController extends Controller {

    public function _actionAfter(){
        /* Nothing to do*/
    }

    public function listStatusAction(){
        $this->verifUser();
        $actesStatusSQL = $this->getObjectInstancier()->get('ActesStatusSQL');
        $result = $actesStatusSQL->getAllStatus();

        echo json_encode(utf8_encode_array($result));
        return true;
    }

    public function nbActesAction(){
        $this->verifUser();

        $status_id = $this->getRecuperateurGet()->getInt('status_id');

        $authority_id = intval($this->me->get("authority_id"));

        $actesTransactionsSQL = $this->getObjectInstancier()->get('ActesTransactionsSQL');
        $nb_transactions = $actesTransactionsSQL->getNbByStatusAndAuthority($status_id,$authority_id);

        $result = array('status_id'=>$status_id,'authority_id'=>$authority_id,'nb_transactions'=>$nb_transactions);

        echo json_encode($result);
        return true;
    }

    public function listActesAction(){
        $this->verifUser();

        $status_id = $this->getRecuperateurGet()->getInt('status_id',0);
        $offset = $this->getRecuperateurGet()->getInt('offset',0);
        $limit = $this->getRecuperateurGet()->getInt('limit',100);

        $authority_id = intval($this->me->get("authority_id"));

        $actesTransactionsSQL = $this->getObjectInstancier()->get('ActesTransactionsSQL');
        $transactions_list = $actesTransactionsSQL->getListByStatusAndAuthority(
            $status_id,
            $authority_id,
            $offset,
            $limit
        );

        $result = array(
            'status_id'=>$status_id,
            'authority_id'=>$authority_id,
            'offset' => $offset,
            'limit' => $limit,
            'transactions'=>$transactions_list
        );

        echo json_encode(utf8_encode_array($result));
        return true;
    }

}