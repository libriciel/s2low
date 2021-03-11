<?php


class MailHeader
{
    private $subject;
    public $fromMail;
    private $fromDescription;
    private $replyToMail;

    public function __construct($subject, $fromMail, $fromDescription)
    {
        $this->setSubjet($subject);
        $this->setFromMail($fromMail);
        $this->setFromDescription($fromDescription);
        $this->setReplyToMail($fromMail);
    }

    public function setSubjet($subject){
        $this->subject = $subject;
    }

    public function setFromMail($fromMail){
        if ($fromMail){
            $this->fromMail = $fromMail;
        }
    }

    public function setFromDescription($fromDescription){
        $this->fromDescription = $fromDescription;
    }

    public function setReplyToMail($replyToMail){
        $this->replyToMail = $replyToMail;
    }

    public function getFromEnveloppeAdressOption(){
        return "-f{$this->fromMail}";
    }

    public function getFromField(){
        return "{$this->fromDescription} <{$this->fromMail}>";
    }

    public function getReplyToField(){
        return $this->replyToMail;
    }

    public function getHeader(){
        return array(
            'From'    => $this->getFromField(),
            'Subject' => $this->subject,
            'Reply-To' => $this->getReplyToField(),
            'Return-path' => $this->getReplyToField()
        );
    }

}