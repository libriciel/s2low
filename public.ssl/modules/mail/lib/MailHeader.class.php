<?php


class MailHeader implements IMailHeader
{
    /** @var string */
    private $subject;
    /** @var string */
    public $fromMail;
    /** @var string */
    private $fromDescription;
    /** @var string */
    private $replyToMail;

    public function __construct(string $subject,string $fromMail,string $fromDescription)
    {
        $this->setSubjet($subject);
        $this->setFromMail($fromMail);
        $this->setFromDescription($fromDescription);
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
        $this->fromDescription = $fromDescription;
    }

    public function setReplyToMail(string $replyToMail) : void
    {
        $this->replyToMail = $replyToMail;
    }

    public function getFromEnveloppeAdressOption() : string
    {
        return "-f{$this->fromMail}";
    }

    public function getFromField() : string
    {
        return "{$this->fromDescription} <{$this->fromMail}>";
    }

    public function getReplyToField(): string
    {
        return $this->replyToMail;
    }

    public function getHeader() : array
    {
        return array(
            'From'    => $this->getFromField(),
            'Subject' => $this->subject,
            'Reply-To' => $this->getReplyToField(),
            'Return-path' => $this->getReplyToField()
        );
    }
}