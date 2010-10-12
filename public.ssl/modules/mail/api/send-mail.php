<?php 

require_once("../include/init.php");

require_once("../controller/mailController.php");


if (isset($_POST['password'])){
	$_POST['psw1'] = $_POST['password'];
	$_POST['psw2'] = $_POST['password'];
}

$_POST['FileNumber'] = count($_FILES);

$MailCtl=new mailController();
ob_start();
$mailId = $MailCtl->executeSend();
ob_end_clean();

if ($result){
	echo "OK:$mailId\n";
} else {
	$erreur = $MailCtl->getLastError();
	echo "ERROR:$erreur\n";
}


