<?php

/**
 * \class MailUtil.class.php
 * \brief fonction commun pour envoiyer les email.
 *
 * Cette classe fournit des méthodes d'envois email de module mail.
 *
 * \author TH ,JMontiel
 * \date :23-04-2008
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

require_once SITEROOT . '/class/include.class.php';


class MailUtil
{
    public $errorMsg;
    private $trace;
    /** @var MailHeader|null  */
    private $mailHeader;

    public function __construct(?IMailHeader $mailHeader = null)
    {
        $this->trace = Trace::getInstance();
        $this->mailHeader = $mailHeader;
    }

    /**
     * \brief extrait un tableau de fichier à partir d'un repertoire
     * \param $root le chemin racine
     * \return array le tableau des fichiers (n'inclue pas les repertoire)
     */
    public function path2array($root)
    {
        $result = array();
        $dh = @opendir($root);
        if (false === $dh) {
            return $result;
        }

        while ($file = readdir($dh)) {
            if ("." == $file || ".." == $file) {
                continue;
            }

            if (is_dir($root . "/" . $file)) {
                $result += $this->path2array($root . "/" . $file);
            } else {
                array_push($result, $root . "/" . $file);
            }
        }
        closedir($dh);
        return $result;
    }

    /**
     * \brief   zip les fichiers dont le chemin est dans array_path et
     *          met le résultat dans $file
     * \param   array_file array tableau des fichiers à zipper
     * \param   $file string nom du fichier de sortie
     * \return  true si ok false sinon. $this->errorMsg contient le message d'erreur
     */
    public function zip($array_file, $file)
    {
        $zip = new ZipArchive();
        $res = $zip->open($file, ZipArchive::CREATE);

        if (! $res) {
            $this->errorMsg = "Impossible de créer l'archive zip $file : erreur " . $res;
            $this->trace->log($this->errorMsg, Trace::$TRACE_ERROR);
            return false;
        }

        foreach ($array_file as $fichier) {
            if (!$zip->addFile($fichier, basename($fichier))) {
                $this->errorMsg = "Impossible de mettre le fichier $fichier dans l'archive $file ";
                $this->trace->log($this->errorMsg, Trace::$TRACE_ERROR);
                return false;
            }
        }
        $zip->close();
        return true;
    }

    /**
   * \brief   envoyer un mail avec des pièces joindures.
   * \param   objet du class : MailMessageEmis
   * \param                    MailTransaction
   * \param                    mail_include_file
   * \param
   * \return  true si ok false sinon.
   */
    public function sendMail($MailMessageEmis, $mailTransaction, $MailIncludeFiles, $send_password = false)
    {
        $text = MAIL_TEXT;
        if ($mailTransaction->getPassword()) {
            $text .= "\n\n";
            if ($send_password) {
                $text .= "Le mot de passe du document est : " . $mailTransaction->getPassword() . "\n";
            } else {
                $text .= "Le document est protégé par un mot de passe";
            }
        }


        $html = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 TRANSITIONAL//EN">
<html>
  <body bgcolor="#ffffff" text="#000000">
';
        $html .= "<p>" . nl2br($text) . "</p>";



        foreach ($MailMessageEmis as $MailEmis) {
            if (defined('MAIL_DEBUG')) {
                echo "<p>mail file number =" . $MailFileNumber;
                "</p>";
                echo "$MailEmis->getEmail()";
            }
            $htmlpart = '';
            $htmlpart .= '
<a href="' . WEBSITE . '/modules/mail/?command=show&mail_emis_id=' . $MailEmis->getId() . '" >Confirmer la reception et lire le courrier en cliquant sur ce lien</a><br>
<p>Information de sécurité : tous les documents ont été testés par l\'anti-virus CLAMAV.</p>
<p>Pour toute demande d\'information, vous pouvez contacter l\'expéditeur précisé dans le contenu du message sur la plateforme sécurisée.</p>
';

            $htmlpart .= '';
            $htmlBody = $html . $htmlpart . "</body></html>";

            $textpart = WEBSITE . "/modules/mail/index.php?command=show&mail_emis_id=" . $MailEmis->getId();
            $textpart .= "\nInformation de sécurité : tous les documents ont été testés par l'anti-virus CLAMAV.\n";

            $crlf = "\n";
            $mime = new Mail_mime($crlf);
            $mime->setTXTBody($text . $textpart);
            $mime->setHTMLBody($htmlBody);

            //do not ever try to call these lines in reverse order
            $body = $mime->get();
            $hdrs = $mime->headers($this->mailHeader->getHeader());

                        $tomime = new Mail_mime($crlf);
                        $tohdrs = array('To' => $MailEmis->getEmail());
                        $tohdrs = $tomime->headers($tohdrs);
                        $to = $tohdrs['To'];

            $mail = new PearMail();
            $mail->sep = $crlf;
            if (!$mail->send($to, $hdrs, $body, $this->mailHeader->getFromEnveloppeAdressOption())) {
                    return false;
            }
        }
        return true;
    }

    public function GetMailMessage()
    {
        // Précédemment il y avait un truc très limité pour tester la boite de retour mais ce n'était a priori pas utilisé
        return null;
    }
}
