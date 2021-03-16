<?php

class MailHeaderLegacy Implements IMailHeader{
    /** @var string */
    private $subject;
    /** @var string */
    public $fromMail;

    public function __construct(string $subject, string $fromMail)
    {
        $this->setSubjet($subject);
        $this->setFromMail($fromMail);
        $this->setReplyToMail($fromMail);
    }

    public function setSubjet(string $subject) : void
    {
        $this->subject = $subject;
    }

    public function setFromMail(string $fromMail) : void
    {
        if ($fromMail){
            $this->fromMail = $fromMail;
        }
    }

    public function setFromDescription(string $fromDescription) : void
    {
        // Just for compatibility purposes
    }

    public function setReplyToMail($replyToMail) : void
    {
        // Just for compatibility purposes
    }

    public function getFromEnveloppeAdressOption() : string
    {
        return "-f{$this->fromMail}";
    }

    public function getFromField() : string
    {
        return $this->fromMail;
    }

    public function getHeader() : array
    {
        return array(
            'From'    => $this->getFromField(),
            'Subject' => $this->subject,
            'Reply-To' => $this->getFromField(),
            'Return-path' => $this->getFromField()
        );
    }
}