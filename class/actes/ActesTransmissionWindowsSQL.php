<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Lib\SQL;

class ActesTransmissionWindowsSQL extends SQL
{
    public function canSend($file_size)
    {
        $sql =  "SELECT rate_limit - consumed as reste FROM actes_transmission_windows " .
        " JOIN actes_transmission_window_hours " .
        " ON actes_transmission_windows.id = actes_transmission_window_hours.transmission_window_id" .
        " WHERE window_begin <= ? AND  window_end >= ? ";

        $date = date('Y-m-d H:i:s');

        $result = $this->queryOne($sql, $date, $date);
        if ($result === false) {
            return true;
        }
        return ($result - $file_size > 0);
    }

    public function addFile($file_size)
    {
        $date = date('Y-m-d H:i:s');
        $sql = "SELECT id FROM actes_transmission_window_hours WHERE window_begin <= ? AND  window_end >= ? ";
        $id = $this->queryOne($sql, $date, $date);

        if (! $id) {
            return;
        }
        $sql =  "UPDATE actes_transmission_window_hours SET consumed = consumed + ? " .
        " WHERE id = ? ";
        $this->query($sql, $file_size, $id);
    }

    public function addWindow($rate_limit)
    {
        $sql = "INSERT INTO actes_transmission_windows(rate_limit) VALUES (?) RETURNING  id";
        return $this->queryOne($sql, $rate_limit);
    }

    public function addWindowHours($transmission_window_id, $window_begin, $window_end)
    {
        $sql = "INSERT INTO actes_transmission_window_hours(transmission_window_id,window_begin,window_end, consumed) " .
                " VALUES (?,?,?,?)";
        $this->query($sql, $transmission_window_id, $window_begin, $window_end, 0);
    }
}
