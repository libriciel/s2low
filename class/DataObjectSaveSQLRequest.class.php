<?php

class DataObjectSaveSQLRequest
{
    /** @var bool  */
    private $valid;
    /** @var string  */
    private $request;
    /** @var array  */
    private $params;

    public function __construct(bool $valid, string $request, array $params)
    {
        $this->valid = $valid;
        $this->request = $request;
        $this->params = $params;
    }

    public function isValid()
    {
        return $this->valid;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getParams()
    {
        return $this->params;
    }
}
