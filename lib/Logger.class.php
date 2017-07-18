<?php

class Logger {

    const TYPE_STDOUT = 'stdout';
    const TYPE_MEMORY = 'memory';

    private $log_type = self::TYPE_STDOUT;

    private $logs = array();

    public function setLogType($log_type){
        $this->log_type = $log_type;
    }

    public function log($part, $message){
        $message = $this->getLogMessage($part,$message);
        if ($this->log_type == self::TYPE_MEMORY){
            $this->logs[] = $message;
        } else {
            echo $message;
        }
    }

    private function getLogMessage($part, $message){
        $date = date("c");
        return "$date [S2low/$part] $message\n";
    }

    public function getAllLog(){
        return $this->logs;
    }

}