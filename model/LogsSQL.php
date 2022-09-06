<?php

namespace S2lowLegacy\Model;

use S2lowLegacy\Lib\SQL;

class LogsSQL extends SQL
{
    public const LEVEL_DEBUG = 0;
    public const LEVEL_INFO = 1;
    public const LEVEL_WARNING = 2;
    public const LEVEL_ERROR = 3;
    public const LEVEL_CRITICAL = 4;


    public function getLogLevelList()
    {
        return array(0 => "Debug","Information","Warning","Error","Critical");
    }

    public function getList($authority_group_id, $authority_id, $user_id, $user_name, $module, $severity, $message, $visibility, $offset, $limit, $date_debut, $date_fin)
    {
        $data_to_retrieve = " logs.id,logs.date,logs.severity,logs.module,logs.issuer,logs.user_id,logs.visibility,logs.message, " .
                            " logs.authority_id, users.name, users.givenname, users.login ";

        $offset = intval($offset);
        $limit = intval($limit);
        $end_query = " ORDER BY id DESC LIMIT $limit OFFSET $offset";

        return $this->getListQuery($data_to_retrieve, $end_query, $authority_group_id, $authority_id, $user_id, $user_name, $module, $severity, $message, $visibility, $date_debut, $date_fin);
    }


    public function getNbLog($authority_group_id, $authority_id, $user_id, $user_name, $module, $severity, $message, $visibility, $date_debut, $date_fin)
    {
        $data =  $this->getListQuery("count(*)", false, $authority_group_id, $authority_id, $user_id, $user_name, $module, $severity, $message, $visibility, $date_debut, $date_fin);
        return $data[0]['count'];
    }

    private function getListQuery($data_to_retrieve, $end_query, $authority_group_id, $authority_id, $user_id, $user_name, $module, $severity, $message, $visibility, $date_debut, $date_fin)
    {
        $sql = "SELECT $data_to_retrieve " .
            " FROM logs" .
            " JOIN users ON users.id=logs.user_id " ;

        $where = array("1=1");
        $data = array();


        if ($authority_group_id) {
            $where[] = " logs.authority_group_id = ? ";
            $data[] = $authority_group_id;
        }

        if ($authority_id) {
            $where[] = " logs.authority_id = ? ";
            $data[] = $authority_id;
        }

        if ($user_id) {
            $where[] = "logs.user_id=?";
            $data[] = $user_id;
        }

        if ($user_name) {
            $where[] = "(users.name ILIKE ? OR users.givenname ILIKE ?) ";
            $data[] = "%$user_name%";
            $data[] = "%$user_name%";
        }

        if ($module) {
            $where[] = "logs.module=?";
            $data[] = $module;
        }

        if ($severity != -1) {
            $where[] = "logs.severity=?";
            $data[] = $severity;
        }

        if ($message) {
            $where[] = "logs.message ILIKE ?";
            $data[] = "%$message%";
        }
        if ($visibility) {
            $visibility_str = "'" . implode("','", $visibility) . "'";
            $where[] = "visibility IN ($visibility_str)";
        }
        if ($date_debut) {
            $where[] = "date > ?";
            $data[] = "$date_debut 00:00:00";
        }
        if ($date_fin) {
            $where[] = "date < ?";
            $data[] = "$date_fin 23:59:59";
        }

        $sql .= " WHERE " . implode(" AND ", $where);

        $sql .= $end_query;
        return $this->query($sql, $data);
    }

    /**
     * FOR TESTING PURPOSE ONLY !!!
     *
     * @param $date
     * @param $severity
     * @param $module
     * @param $issuer
     * @param $user_id
     * @param $visibility
     * @param $message
     * @param $timestamp
     */
    public function addLog($date, $severity, $module, $issuer, $user_id, $visibility, $message, $timestamp): int
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
        $sql = "INSERT INTO logs(date,severity,module,issuer,user_id,visibility,message,timestamp,authority_id,authority_group_id) VALUES (?,?,?,?,?,?,?,?,?,?) RETURNING id";
        return $this->queryOne($sql, $date, $severity, $module, $issuer, $user_id, $visibility, $message, $timestamp, $authority_id, $authority_group_id);
    }

    public function getMinDate()
    {
        $sql = "SELECT MIN(date) FROM logs";
        return $this->queryOne($sql);
    }

    public function getLastLog()
    {
        $sql = "SELECT * FROM logs ORDER BY date DESC LIMIT 1";
        return $this->queryOne($sql);
    }
}
