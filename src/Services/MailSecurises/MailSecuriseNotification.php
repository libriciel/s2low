<?php

namespace S2low\Services\MailSecurises;

use S2lowLegacy\Mail\IMailHeader;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class MailSecuriseNotification
{
    /**
     * @var \Symfony\Component\Mailer\MailerInterface
     */
    private MailerInterface $mailer;
    /**
     * @var \Twig\Environment
     */
    private Environment $twig;


    public function __construct(MailerInterface $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function send(array $MailMessagesEmis, $password, IMailHeader $mailHeader, $send_password = false): bool
    {
        $text = $this->twig->render('mailtext.twig', [
            "texte" => MAIL_TEXT,
            "password" => $password,
            "send_password" => $send_password
        ]);

        foreach ($MailMessagesEmis as $mailMessageEmis) {
            $html = $this->twig->render('mail.html.twig', [
                "subject" => $mailHeader->getHeader()['Subject'],
                "id" => $mailMessageEmis->getId(),
                "website_mail" => WEBSITE_MAIL,
                "text" => nl2br($text),
                "send_password" => $send_password
            ]);

            $email = ( new Email())
                ->from($mailHeader->getHeader()['From'])
                ->to($mailMessageEmis->getEmail())
                ->replyTo($mailHeader->getHeader()['Reply-To'])
                ->subject($mailHeader->getHeader()['Subject'])
                ->text($text)
                ->html($html);

            try {
                $this->mailer->send($email);
            } catch (\Exception $e) {
                return false;
            }
        }
        return true;
    }
}
