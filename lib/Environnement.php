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
        $this->getWrapper = new Recuperateur($get, $forceConversionFromIso);
        $this->postWrapper = new Recuperateur($post, $forceConversionFromIso);
        $this->requestWrapper = new Recuperateur($request, $forceConversionFromIso);
        $this->sessionWrapper = new SessionWrapper($session);
        $this->serverWrapper = new Recuperateur($server, $forceConversionFromIso);
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
