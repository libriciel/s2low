<?php

namespace S2lowLegacy\Class;

use S2lowLegacy\Lib\SessionWrapper;

class S2lowRedirect
{
    public const SESSION_MESSAGE_KEY = 'error';

    private $sessionWrapper;
    private $website_ssl;
    private $website;

    public function __construct($website_ssl, $website, SessionWrapper $sessionWrapper)
    {
        $this->sessionWrapper = $sessionWrapper;
        $this->website_ssl = $website_ssl;
        $this->website = $website;
    }

    public function redirect($url_path, $error_message = "")
    {
        $this->internal_redirect($this->website_ssl, $url_path, $error_message);
    }

    public function redirectHttp($url_path, $error_message = "")
    {
        $this->internal_redirect($this->website, $url_path, $error_message);
    }

    private function internal_redirect($site_base, $url_path, $error_message)
    {
        $url = trim($site_base, "/") . "/" . ltrim($url_path, "/");
        $this->sessionWrapper->set(self::SESSION_MESSAGE_KEY, $error_message);
        header_wrapper("Location: $url");
        exit_wrapper(1, $error_message);
    }
}
