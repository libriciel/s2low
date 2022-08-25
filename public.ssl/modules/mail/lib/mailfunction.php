<?php

use S2lowLegacy\Class\Mailer;

/**
 * \class mailfunction.php
 * \brief Cette fichier fournie des fonctions utilitaires pour envoyer les emails.
 * \author TH  ,JMontiel
 * \date :23-04-2008
 *
 *
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

function checkEmail($email, $antispam = false)
{

    if (!$email || !preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+([\.][a-z0-9-]+)+$/i", $email)) {
        return false;
    }

    if ($antispam) {
        $email = str_replace("@", " at ", $email);
        $email = str_replace(".", " dot ", $email);
        return $email;
    } else {
        return true;
    }
}
function checkAllEmail($emailtext)
{
    $mailer = new Mailer();
    // $emailtext=substr($emailtext,0,-1);
   // le séparateur  is vircule
    $mails = explode(",", $emailtext);
    foreach ($mails as $mail) {
        $mail = str_replace("[", "<", $mail);
        $mail = str_replace("]", ">", $mail);
        if ($mail != null) {
            if ($mailer->isValidMail($mail) == false) {
                return false;
            }
        }
    }
    return true;
}
