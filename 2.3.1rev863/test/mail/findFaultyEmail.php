<?php

require_once(__DIR__."/../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/class/Mailer.class.php');

require_once(SITEROOT."/public.ssl/modules/mail/controller/mailController.php");
require_once(SITEROOT."/public.ssl/modules/mail/lib/GroupeMail.class.php");

function checkAllEmail($emailtext) {
	$mailer = new Mailer();
	$mails=explode(",",$emailtext);
	foreach ($mails as $mail)
	{
		$mail = str_replace ("[","<", $mail);
		$mail = str_replace("]",">",$mail);
		if ($mail!=null)

			if ($mailer->isValidMail($mail)==false){
			echo $mail;
			return false;
		}
	}
	return true;
}

$me = new User(8279);

$me->init();