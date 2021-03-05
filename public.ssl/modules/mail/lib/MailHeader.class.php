<?php


class MailHeader
{
    private $subject;
    public $from;

    public function __construct()
    {
        $this->setSubjet(MAIL_MESSAGE);
        $this->setFrom(MAIL_TEDETIS_FROM);
    }

    public function setSubjet($subject){
        $this->subject = $subject;
    }

    public function setFrom($from){
        if ($from){
            $this->from = $from;
        }
    }

    public function getHeader(){
        return array(
            'From'    => $this->from,
            'Subject' => $this->subject,
            'Reply-To' => $this->from,
            'Return-path' => $this->from
        );
    }

}