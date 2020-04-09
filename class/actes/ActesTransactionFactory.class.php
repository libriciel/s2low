<?php

class ActesTransactionFactory{
    public function get($transactionId){
        $actesTransaction = new ActesTransaction();
        $actesTransaction->setId($transactionId);
        $actesTransaction->init();
        return $actesTransaction;
    }
}