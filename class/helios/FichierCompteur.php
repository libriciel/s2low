<?php

namespace S2lowLegacy\Class\helios;

class FichierCompteur
{
    private $file_path;

    public function __construct($file_path)
    {
        $this->file_path = $file_path;
    }

    public function getNumero()
    {
        $fd = fopen($this->file_path, "c+");
        flock($fd, LOCK_EX);

        $num = fread($fd, 1024);
        fseek($fd, 0);
        ftruncate($fd, 0);
        $num = intval($num);
        $num++;
        $num = sprintf("%03d", $num);
        fwrite($fd, $num);

        flock($fd, LOCK_UN);
        fclose($fd);
        return $num;
    }
}
