<?php

class S2lowRedirect {

    const SESSION_MESSAGE_KEY = 'error';

    private $sessionWrapper;
    private $website_ssl;

    public function __construct($website_ssl, SessionWrapper $sessionWrapper) {
        $this->sessionWrapper = $sessionWrapper;
        $this->website_ssl = $website_ssl;
    }

    public function redirect($url_path,$error_message = ""){
        $this->sessionWrapper->set(self::SESSION_MESSAGE_KEY,$error_message);
        $url = trim($this->website_ssl,"/") ."/". ltrim($url_path,"/");
        header_wrapper("Location: $url");
        exit_wrapper();
    }

}