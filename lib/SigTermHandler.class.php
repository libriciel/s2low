<?php

class SigTermHandler {

    private $sig_term_called = false;

    public function __construct(){
        pcntl_signal(SIGTERM, array($this,"handle"));
    }

    public function isSigtermCalled(){
        return $this->sig_term_called;
    }

    //Please, do not call this function directly
    public function handle($signo){
        echo "Signal $signo appelé !\n";
        $this->sig_term_called = true;
    }
}