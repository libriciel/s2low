<?php

abstract class AbstractMailHeader{
    /** @var string */
    protected $message;
    /** @var string */
    protected $fromMail;
    /** @var string */
    protected $fromDescription;
    /** @var string */
    protected $replyToMail;
    /** @var string */
    protected $authorityName;

    public function __construct(string $subject,string $fromMail,string $fromDescription)
    {
        $this->setMessage($subject);
        $this->setFromMail($fromMail);
        $this->setFromDescription($fromDescription);
        //$this->setReplyToMail($fromMail);
    }


    public function setMessage(string $message) : void
    {
        $this->message = $message;
    }

    public function setAuthorityName(string $name) : void
    {
        $this->authorityName = $name;
    }

    public function setFromMail(string $fromMail) : void
    {
        if (!is_null($fromMail)){
            $this->fromMail = $fromMail;
        }
    }

    public function setFromDescription(string $fromDescription) : void
    {
        if(!is_null($fromDescription)){
            $this->fromDescription = $fromDescription;
        }
    }

    /*public function setReplyToMail(string $replyToMail) : void
    {
        $this->replyToMail = $replyToMail;
    }*/

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