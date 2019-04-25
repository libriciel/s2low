<?php

class ScriptSleeping {

    private $min_execution_time = 10;
    private $start;

    private $logger;
    private $script_name;

    public function __construct(S2lowLogger $logger) {
        $this->logger = $logger;
    }

    public function setMinExecutionTime($time_in_second){
        $this->min_execution_time = $time_in_second;
    }

    public function debut($script_name){
        $this->start = time();
        $this->logger->info($script_name,"Début du script");
        $this->script_name = $script_name;
    }

    public function fin(){
        $this->logger->info($this->script_name,"Fin du script");
        $sleep = $this->min_execution_time - (time() -$this->start);
        if ($sleep > 0){
            $this->logger->info($this->script_name,"Arret du script : $sleep");
            sleep($sleep);
        }
    }

}