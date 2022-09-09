<?php

namespace S2lowLegacy\Class;

require_once("Mail/RFC822.php");                    //Fixé plus tard
require_once("PEAR.php");                           // Lors du remplacement par le mail Symfony

use Mail_RFC822;
use PEAR;


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

    /**
     * @deprecated
     * @param $mail
     * @return bool
     */
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
