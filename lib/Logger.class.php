<?php

class Logger {


    public function log($part, $message){
        $date = date("c");
        echo "$date [S2low/$part] $message\n";
    }

}