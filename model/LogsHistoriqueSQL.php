<?php

namespace S2lowLegacy\Model;

use Exception;
use S2lowLegacy\Lib\SQL;
use S2lowLegacy\Lib\SQLQuery;

class LogsHistoriqueSQL extends SQL
{
    public function vidange($nb_month_to_keep)
    {
        $date = date("Y-m-d H:i:s", strtotime("-{$nb_month_to_keep} month"));

        $this->getConnection()->beginTransaction();
        try {
            $sql = "INSERT INTO logs_historique SELECT * FROM logs WHERE logs.date<?";
            $this->query($sql, $date);

            $sql = "DELETE FROM logs WHERE logs.date<?";
            $this->query($sql, $date);

            $this->getConnection()->commit();
        } catch (Exception $e) {
            $this->getConnection()->rollBack();
        }
    }

    public function getMaxDate()
    {
        $sql = "SELECT MAX(date) FROM logs_historique";
        return $this->queryOne($sql);
    }

    /**
     * @param LogsRequestData $logsRequestData
     * @param $output_filename
     * @throws Exception
     */
    public function request(LogsRequestData $logsRequestData, $output_filename)
    {
        $file_handle = fopen($output_filename, "w");
        if (! $file_handle) {
            throw new Exception("Impossible d'ouvrir le fichier $output_filename");
        }

        $this->requestOnTable($logsRequestData, "logs_historique", $file_handle);
        $this->requestOnTable($logsRequestData, "logs", $file_handle);
        fclose($file_handle);
    }

    private function requestOnTable(LogsRequestData $logsRequestData, $table, $file_handle)
    {

        if ($logsRequestData->authority_group_id) {
            $where[] = " $table.authority_group_id = ? ";
            $data[] = $logsRequestData->authority_group_id;
        }

        if ($logsRequestData->authority_id) {
            $where[] = " $table.authority_id = ? ";
            $data[] = $logsRequestData->authority_id;
        }

        if ($logsRequestData->user_id) {
            $where[] = "$table.user_id=?";
            $data[] = $logsRequestData->user_id;
        }

        $where[] = "$table.date >= ?";
        $data[] = "{$logsRequestData->date_debut} 00:00:00";
        $where[] = "$table.date <= ?";
        $data[] = "{$logsRequestData->date_fin} 23:59:59";

        $where = " WHERE " . implode(" AND ", $where);

        $data_to_retrieve = " $table.id,$table.date,$table.severity," .
            " $table.module,$table.issuer,$table.user_id, " .
            " $table.visibility,$table.message, " .
            " $table.authority_id," .
            " users.name as user_name, users.givenname," .
            " authorities.name as authority_name , " .
            " authority_groups.name as group_name";

        $sql = "SELECT $data_to_retrieve FROM $table " .
            " LEFT JOIN users ON users.id=$table.user_id " .
            " LEFT JOIN authorities ON authorities.id = $table.authority_id " .
            " LEFT JOIN authority_groups ON $table.authority_group_id = authority_groups.id " .
            " $where " .
            " ORDER BY id";

        $this->getSQLQuery()->prepareAndExecute($sql, $data);

        while ($this->getSQLQuery()->hasMoreResult()) {
            $result = $this->getSQLQuery()->fetch();
            fputcsv($file_handle, $result);
        }
    }
}
