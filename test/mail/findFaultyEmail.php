<?php

use S2lowLegacy\Class\Mailer;
use S2lowLegacy\Class\User;

require_once(__DIR__ . "/../../init/init.php");
\S2lowLegacy\Class\LegacyObjectsManager::setLegacyObjectInstancier();

function checkAllEmail($emailtext)
{
    $mailer = new Mailer();
    $mails = explode(",", $emailtext);
    foreach ($mails as $mail) {
        $mail = str_replace("[", "<", $mail);
        $mail = str_replace("]", ">", $mail);
        if ($mail != null) {
            if ($mailer->isValidMail($mail) == false) {
                echo $mail;
                return false;
            }
        }
    }
    return true;
}

$me = new User(8279);

$me->init();
