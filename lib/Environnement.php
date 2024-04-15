<?php

namespace S2lowLegacy\Lib;

class Environnement
{
    private $getWrapper;
    private $postWrapper;
    private $requestWrapper;
    private $serverWrapper;
    private $sessionWrapper;

    public function __construct($get, $post, $request, &$session, $server, bool $forceConversionFromIso = false)
    {
        $isHTTPAuthentification = isset($server['PHP_AUTH_USER']);
        $isNounceAuthentification = isset($get['login']) && isset($get['nounce']) && isset($get['hash']);
        $needsConversionFromIso = ($isHTTPAuthentification || $isNounceAuthentification) && $forceConversionFromIso;
        $this->getWrapper = new Recuperateur($get, $needsConversionFromIso);
        $this->postWrapper = new Recuperateur($post, $needsConversionFromIso);
        $this->requestWrapper = new Recuperateur($request, $needsConversionFromIso);
        $this->sessionWrapper = new SessionWrapper($session);
        $this->serverWrapper = new Recuperateur($server, $needsConversionFromIso);
    }

    public function session()
    {
        return $this->sessionWrapper;
    }

    public function get()
    {
        return $this->getWrapper;
    }

    public function post()
    {
        return $this->postWrapper;
    }

    public function request()
    {
        return $this->requestWrapper;
    }
    public function server()
    {
        return $this->serverWrapper;
    }
}
