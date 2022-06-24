<?php

require_once("PEAR.php");
require_once("Mail.php");
require_once("Mail/mime.php");

class PearMail extends Mail
{
    //EP : A priori la classe Pear::Mail a été surchargé afin de pouvoir integrer le champs FROM
    public function send($recipients, $headers, $body, $from = '')
    {

        $this->_sanitizeHeaders($headers);

        // if we're passed an array of recipients, implode it.
        if (is_array($recipients)) {
            $recipients = implode(', ', $recipients);
        }

        // get the Subject out of the headers array so that we can
        // pass it as a seperate argument to mail().
        $subject = '';
        if (isset($headers['Subject'])) {
            $subject = $headers['Subject'];
            unset($headers['Subject']);
        }

        // flatten the headers out.
        $prepareHeader = Mail::prepareHeaders($headers);


        if (!$prepareHeader || PEAR::isError($prepareHeader)) {
            print_r($prepareHeader);
            throw new Exception("Impossible de parser les en-têtes du mail ($prepareHeader)");
        }


        $text_headers = $prepareHeader[1];



        $result = mail($recipients, $subject, $body, $text_headers, $from);
        return $result;
    }
}
