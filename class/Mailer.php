<?php

namespace S2lowLegacy\Class;

require_once("Mail/RFC822.php");                    //Fixé plus tard
require_once("PEAR.php");                           // Lors du remplacement par le mail Symfony
require_once(SITEROOT . "/class/PearMail.php");

use Mail_RFC822;
use PEAR;
use Mail_mime;


class Mailer
{
    protected const FILESIZE_LIMIT =  10485760; /* 10 Mio */

    protected $recipients;
    protected $lastError;
    protected $fichier;
    protected $dataAsFile;

    public function __construct()
    {
        $this->recipients = array();
        $this->fichier = array();
        $this->dataAsFile = array();
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function addRecipient($recipient)
    {
        if (! $this->isValidMail($recipient)) {
            return false;
        }
        $this->recipients[] = $recipient;
        return true;
    }

    public function addFile($file)
    {
        $this->fichier[] = $file;
    }

    public function addStringAsFile($filename, $data)
    {
        $this->dataAsFile[] = array('filename' => $filename,'data' => $data);
    }

    public function addComplexRecipient($recipient)
    {
        return $this->addRecipient($this->getNormalizedEmailAdresse($recipient));
    }

    public function isValidMail($mail)
    {
        $mail_RFC822 = new Mail_RFC822();
        $lo_mail = $mail_RFC822->parseAddressList($mail, null, false);
        if (PEAR::isError($lo_mail)) {
            return false;
        } elseif ($lo_mail[0]->host == 'localhost') {
            return false;
        }
        return true;
    }

    /**
     * @deprecated
     * @param $subject
     * @param $body
     * @return bool|void
     * @throws \Exception
     */
    public function sendMail($subject, $body)
    {


        assert(!!$subject);
        assert(!!$body);
        assert(!!$this->recipients);

        foreach ($this->recipients as $recipient) {
            $crlf = "\n";
            $mime = new Mail_mime($crlf);
            $mime->setTXTBody($body);

            $hdrs = array(
                'From'    => TDT_FROM_EMAIL,
                'Subject' => $subject,
            );

            foreach ($this->fichier as $file) {
                if (filesize($file) < self::FILESIZE_LIMIT) {
                    $mime->addAttachment($file);
                }
            }

            foreach ($this->dataAsFile as $dataAsFile) {
                $mime->addAttachment($dataAsFile['data'], 'application/octet-stream', $dataAsFile['filename'], false);
            }
          //do not ever try to call these lines in reverse order
            $body = $mime->get();
            $hdrs = $mime->headers($hdrs);

            $mail = new PearMail();
            $mail->sep = $crlf;
            if (!$mail->send($recipient, $hdrs, $body)) {
                $this->lastError = "Erreur lors de l'envoi d'un message vers $recipient" ;
                return false;
            }
        }

        return true;
    }

    public function getNormalizedEmailAdresse(array $recipient)
    {
        assert(!!$recipient["email"]);

        $recip = "";
        if (! empty($recipient['givenname'])) {
            $recip .= $recipient["givenname"] . " ";
        }
        if (! empty($recipient['name'])) {
            $recip .=  $recipient["name"];
        }
        if ($recip) {
            $recip .= " <" . $recipient["email"] . ">";
        } else {
            $recip = $recipient['email'];
        }
        return $recip;
    }
}
