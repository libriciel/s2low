<?php

use S2low\Services\MailActesNotifications\MailerSymfony;
use S2lowLegacy\Class\Mailer;
use S2lowLegacy\Class\User;

require_once(__DIR__ . "/../../init/init.php");
\S2lowLegacy\Class\LegacyObjectsManager::setLegacyObjectInstancier();

function checkAllEmail($emailtext)
{
    $mails = explode(",", $emailtext);
    foreach ($mails as $mail) {
        $mail = str_replace("[", "<", $mail);
        $mail = str_replace("]", ">", $mail);
        if ($mail != null) {
            if (!MailerSymfony::isValidMail($mail)) {
                echo $mail;
                return false;
            }
        }
    }
    return true;
}

$me = new User(8279);

$me->init();
