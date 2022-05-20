<?php

namespace S2low\Services\ProcessCommand;

class AnalysedOutput
{
    /**
     * @var array
     */
    private $blockingErrors;
    /**
     * @var array
     */
    private $nonBlockingErrors;

    public function __construct(string $result = "", array $blockingErrors =[], array $nonBlockingErrors = []){
        $this->blockingErrors = $blockingErrors;
        $this->nonBlockingErrors = $nonBlockingErrors;
        $this->result = $result;

    }

    public function hasBlockingErrors() : bool
    {
        return !empty($this->blockingErrors);
    }

    public function getFirstBlockingErrorMessage() : string
    {
        return $this->blockingErrors[0];
    }

    public function hasNonBlockingErrors() : bool
    {
        return !empty($this->blockingErrors);
    }

    public function getNonBlockingErrors() : string
    {
        return $this->nonBlockingErrors[0];
    }

    public function getResult() : string
    {
        return $this->result;
    }

}