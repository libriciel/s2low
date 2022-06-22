<?php

class LogsRequestSQL extends SQL
{
    public function newRequest(LogsRequestData $logsRequestData)
    {
        $sql = "INSERT INTO logs_request(user_id_demandeur,user_id,authority_id,authority_group_id,date_debut,date_fin,state,date_demande) " .
                " VALUES (?,?,?,?,?,?,?,now()) RETURNING ID";

        $id = $this->queryOne(
            $sql,
            $logsRequestData->user_id_demandeur,
            $logsRequestData->user_id,
            $logsRequestData->authority_id,
            $logsRequestData->authority_group_id,
            $logsRequestData->date_debut,
            $logsRequestData->date_fin,
            LogsRequestData::STATE_ASKING
        );
        return $id;
    }


    public function getAllByState($state)
    {
        $sql = "SELECT * FROM logs_request WHERE state=? ORDER BY date_demande";
        return $this->query($sql, $state);
    }

    public function getAllByUser($user_id)
    {
        $sql = "SELECT * FROM logs_request WHERE user_id_demandeur=? ORDER BY date_demande DESC";
        return $this->query($sql, $user_id);
    }

    public function hasRequest($user_id)
    {
        $sql = "SELECT * FROM logs_request WHERE user_id_demandeur=? LIMIT 1";
        return $this->queryOne($sql, $user_id) ? true : false;
    }

    public function delete($id, $user_id)
    {
        $sql = "DELETE FROM logs_request WHERE id=? AND user_id_demandeur=? AND state=?";
        $this->query($sql, $id, $user_id, LogsRequestData::STATE_ASKING);
    }

    public function forceDelete($id)
    {
        $sql = "DELETE FROM logs_request WHERE id=?";
        $this->query($sql, $id);
    }

    public function setAvailable($id)
    {
        $sql = "UPDATE logs_request SET state=?,date_traitement=now() WHERE id=?";
        $this->query($sql, LogsRequestData::STATE_AVAILABLE, $id);
    }

    public function hasPendingRequest($user_id)
    {
        $sql = "SELECT * FROM logs_request WHERE user_id_demandeur=? AND state=? LIMIT 1";
        return $this->queryOne($sql, $user_id, LogsRequestData::STATE_ASKING) ? true : false;
    }

    public function getInfo($id)
    {
        $sql = "SELECT * FROM logs_request WHERE id=?";
        return $this->queryOne($sql, $id);
    }

    public function getOldRequest()
    {
        $yesterday = date("Y-m-d H:i:s", strtotime("-1 day"));
        $sql = "SELECT * FROM logs_request WHERE date_traitement<?";
        return $this->query($sql, $yesterday);
    }
}
