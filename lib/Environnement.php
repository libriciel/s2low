<?php

namespace S2lowLegacy\Lib;

class Environnement
{
    private $getWrapper;
    private $postWrapper;
    private $requestWrapper;
    private $serverWrapper;
    private $sessionWrapper;

    public function __construct($get, $post, $request, &$session, $server)
    {
        $this->getWrapper = new Recuperateur($get);
        $this->postWrapper = new Recuperateur($post);
        $this->requestWrapper = new Recuperateur($request);
        $this->sessionWrapper = new SessionWrapper($session);
        $this->serverWrapper = new Recuperateur($server);
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
