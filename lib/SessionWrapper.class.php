<?php

class SessionWrapper {

    private $session;

    public function __construct(array & $session) {
        $this->session = & $session;
    }

    public function get($key,$default_value = false){
        if (! isset($this->session[$key])){
            return $default_value;
        }
        return $this->session[$key];
    }

    public function set($key,$value){
        $this->session[$key] = $value;
    }
}