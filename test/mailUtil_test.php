<?php
require_once("../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/mail/class/MailUtil.class.php');

function test($nom, $attendu, $resultat){
	if ($attendu == $resultat){
		echo "$nom : [OK]\n";
	} else {
		echo "$nom : [FAIL] - attendu : $attendu - resultat : $resultat.\n";
	}
	echo "<br/>";
}

$trace = Trace::getInstance();

$mailUtil = new MailUtil();
$mailUtil_test_path = SITEROOT ."test/mailUtil/";

/*test(	"Test d'un répertoire sans virus",
		true,
		$mailUtil->checkSanity($mailUtil_test_path."sans_virus")
	);

test(	"Test d'un répertoire avec virus",
		false,
		$mailUtil->checkSanity($mailUtil_test_path."avec_virus")
	);*/
$tab = $mailUtil->path2array($mailUtil_test_path);

test (	"Test du zip d'un repertoire",
		true,
		$mailUtil->zip($tab,$mailUtil_test_path."../testzip.zip")
	);

test ( "Test de la signature d'un fichier",
		true,
		$mailUtil->sign($mailUtil_test_path."../testzip.zip",$mailUtil_test_path."../testzip.zip.p7")
	);	
	
test ("Test de l'envoie d'un mail",
		true,
		$mailUtil->sendSignedMail("epommate@ntsys.fr","Test mail signe",12,"Hé bien ! filles d'enfer, vos mains sont-elles priées ? Pour qui sont ces serpents qui sifflent sur vos têtes ? à qui destinez-vous l'appareil qui vous suit")
		);	

$cmd = EDDOS_BINDIR . "/eddos_crypt -action verify -p7infile " . $mailUtil_test_path."../testzip.zip.p7" . " -datainfile " . $mailUtil_test_path."../testzip.zip" . " -cacertpath " . AUTHORIZED_SIGN_CA_PATH;

Trace::wrap_exec($cmd, $out, $ret);		
		
test("Vérification de la signature",0,$ret);

