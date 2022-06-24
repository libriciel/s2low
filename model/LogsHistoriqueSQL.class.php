<?php

class LogsHistoriqueSQL extends SQL
{
    public function vidange($nb_month_to_keep)
    {
        $date = date("Y-m-d H:i:s", strtotime("-{$nb_month_to_keep} month"));

        $this->query("BEGIN");
        try {
            $sql = "INSERT INTO logs_historique SELECT * FROM logs WHERE logs.date<?";
            $this->query($sql, $date);

            $sql = "DELETE FROM logs WHERE logs.date<?";
            $this->query($sql, $date);

            $this->query("COMMIT");
        } catch (Exception $e) {
            $this->query("ROLLBACK");
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

    public function getLogOlderThanNbDaysWithTimestamp(int $nb_days, int $limit = 0): SQLQuery
    {
        $date = date("Y-m-d", strtotime("today -{$nb_days}days"));
        $sql = "SELECT id,date,timestamp FROM logs_historique WHERE date<? ";
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        $this->getSQLQuery()->prepareAndExecute($sql, $date);
        return $this->getSQLQuery();
    }

    public function getInfo(int $log_id): array
    {
        $sql = "SELECT * FROM logs_historique WHERE id=?";
        return $this->queryOne($sql, $log_id);
    }

    public function deleteTimestamp(int $log_id): void
    {
        $sql = "UPDATE logs_historique SET timestamp='' WHERE id=?";
        $this->query($sql, $log_id);
    }

    /**
     * @param $date
     * @param $severity
     * @param $module
     * @param $issuer
     * @param $user_id
     * @param $visibility
     * @param $message
     * @param $timestamp
     * @return int
     * FOR TESTING PURPOSE ONLY !!!
     */
    public function addLog($id, $date, $severity, $module, $issuer, $user_id, $visibility, $message, $timestamp): int
    {
        $sql = "SELECT authority_id,authority_group_id FROM users WHERE id=?";
        $line = $this->queryOne($sql, $user_id);
        if ($line) {
            $authority_id = $line['authority_id'];
            $authority_group_id = $line['authority_group_id'];
        } else {
            $authority_id = false;
            $authority_group_id = false;
        }
        $sql = "INSERT INTO logs_historique(id,date,severity,module,issuer,user_id,visibility,message,timestamp,authority_id,authority_group_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
        return $this->queryOne($sql, $id, $date, $severity, $module, $issuer, $user_id, $visibility, $message, $timestamp, $authority_id, $authority_group_id);
    }
}
