<?php

class HeliosTransmissionWindowsSQL
{
    private $sqlQuery;

    public function __construct(SQLQuery $sqlQuery)
    {
        $this->sqlQuery = $sqlQuery;
    }

    public function canSend($file_size)
    {
        $sql =  "SELECT rate_limit - consumed as reste " .
        " FROM helios_transmission_windows" .
        " JOIN helios_transmission_window_hours " .
        " ON helios_transmission_windows.id = helios_transmission_window_hours.transmission_window_id" .
        " WHERE window_begin <= ? AND  window_end >= ? ";

        $date = date('Y-m-d H:i:s');

        $result = $this->sqlQuery->queryOne($sql, $date, $date);
        if ($result === false) {
            return true;
        }
        return ($result - $file_size > 0);
    }

    public function addFile($file_size)
    {
        $date = date('Y-m-d H:i:s');
        $sql = "SELECT id FROM helios_transmission_window_hours WHERE window_begin <= ? AND  window_end >= ? ";
        $id = $this->sqlQuery->queryOne($sql, $date, $date);

        if (! $id) {
            return;
        }
        $sql =  "UPDATE helios_transmission_window_hours" .
        " SET consumed = consumed + ? " .
        " WHERE id = ? ";
        $this->sqlQuery->query($sql, $file_size, $id);
    }
}
