<?php

namespace S2low\Services\MailActesNotifications;

use Exception;
use S2lowLegacy\Class\Mailer;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerSymfony extends Mailer
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
        parent::__construct();
    }

    /**
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
            $email = (new Email())
                ->from(TDT_FROM_EMAIL)
                ->to($recipient)
                ->subject($subject)
                ->text($body);

            foreach ($this->fichier as $file) {
                if (filesize($file) < self::FILESIZE_LIMIT) {
                    $email->attachFromPath($file);
                }
            }

            foreach ($this->dataAsFile as $dataAsFile) {
                $email->attach($dataAsFile['data'], $dataAsFile['filename'], 'application/octet-stream');
            }
            try {
                $this->mailer->send($email);
            } catch (TransportExceptionInterface $e) {
                $this->lastError = "[TransportExceptionInterface] Erreur lors de l'envoi d'un message vers $recipient : " . $e->getMessage() ;
                return false;
            } catch (Exception $e) {
                $this->lastError = "Erreur lors de l'envoi d'un message vers $recipient : " . $e->getMessage() ;
                return false;
            }
        }

        return true;
    }
}
