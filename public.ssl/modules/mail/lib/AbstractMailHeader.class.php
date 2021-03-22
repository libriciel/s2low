<?php

abstract class AbstractMailHeader{
    /** @var string */
    protected $message;
    protected $s2lowFromMail;
    /** @var string */
    protected $fromMail;
    /** @var string */
    protected $fromDescription;
    /** @var string */
    protected $authorityName;

    public function __construct(string $subject, string $s2lowFromMail, string $fromDescription)
    {
        $this->setMessage($subject);
        $this->setS2LowFromMail($s2lowFromMail);
        $this->setFromMail($s2lowFromMail);
        $this->setFromDescription($fromDescription);
    }


    public function setMessage(string $message) : void
    {
        $this->message = $message;
    }

    public function setAuthorityName(string $name) : void
    {
        $this->authorityName = $name;
    }

    public function setS2LowFromMail(?string $s2lowFromMail) : void
    {
        $this->s2lowFromMail = $s2lowFromMail;
    }

    public function setFromMail(?string $fromMail) : void
    {
        if (!is_null($fromMail)){
            $this->fromMail = $fromMail;
        }
    }

    public function setFromDescription(?string $fromDescription) : void
    {
        if(!is_null($fromDescription)){
            $this->fromDescription = $fromDescription;
        }
    }

    public function getFromEnveloppeAdressOption() : string
    {
        return "-f{$this->fromMail}";
    }

    protected function getSubject() : string
    {
        if(!isset($this->authorityName)){
            return $this->message;
        }
        return "[".$this->authorityName."] ".$this->message;
    }
}