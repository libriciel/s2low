<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : C. Pop Mars 2007
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à la
 * dématèrialisation de l'administration. 
 *
 * Ce logiciel est régi par la licence CeCILL soumise au droit français et
 * respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusée par le CEA, le CNRS et l'INRIA 
 * sur le site "http://www.cecill.info".
 *
 * En contrepartie de l'accessibilité au code source et des droits de copie,
 * de modification et de redistribution accordés par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitée.  Pour les mêmes raisons,
 * seule une responsabilité restreinte pèse sur l'auteur du programme,  le
 * titulaire des droits patrimoniaux et les concédants successifs.
 *
 * A cet égard  l'attention de l'utilisateur est attirée sur les risques
 * associés au chargement,  à l'utilisation,  à la modification et/ou au
 * développement et à la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à 
 * manipuler et qui le réserve donc à des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à charger  et  tester  l'adéquation  du
 * logiciel à leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * à l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder à cet en-tête signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/
/**
/*\file helios_script_reception.php
 * \brief Page permettannt l'import d'un fichier envoyé par POST HTTP et son "forward" vers le servlet
 * Si tout est okay => "OK\n"; si ereur => "KO\n"
 * \author Cristina Pop <cpop@alternancesoft.com>
 * \date 11.03.2007
 */

// Configuration
require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosTransactionWorkflow.class.php');
require_once (SITEROOT . '/class/User.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/antivirus.class.php');
// Instanciation du module courant
$module = new Module();
if (!$module->initByName("helios")) {
  $_SESSION["error"] = "Erreur d'initialisation du module";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$me = new User();
//l'utilisateur'

if (!$me->authenticate()) {
  $_SESSION["error"] = "Ehec de l'authentification";
  header("Location: " . WEBSITE);
  exit ();
}

$nomUSer = $me->get("name");
$userId = $me->getId();

if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

//TODO cela dans la configuration!!!...    

$ok = 0;
$ko = 0;

$uploaddir = HELIOS_FILES_UPLOAD_ROOT;

$uploadFile_baseName = $_FILES['enveloppe']['name'];
//$signFile_baseName = $_FILES['signature']['name'];

$uploadfile = $uploaddir . basename($uploadFile_baseName);
//$signfile = $uploaddir . basename($signFile_baseName);

if (move_uploaded_file($_FILES['enveloppe']['tmp_name'], $uploadfile)) {

if (!Antivirus::checkArchiveSanity($uploadfile))
{
	$_SESSION["error"] = Antivirus::$errorMsg;
  header("Location: " . WEBSITE_SSL);
  exit ();
}
	//calculate the sha1 form the content of the file.
	$SHA1=sha1_file($uploadfile);
	
  //move_uploaded_file($_FILES['signature']['tmp_name'], $signfile);
  $ht = new HeliosTransaction();
  $htw = new HeliosTransactionWorkflow();
    //insertion (idUSer, filename, signed) dans la table helios_transactions  => un id de la transaction
  // où filename = le nom du fichier inclut dans le fichier message 
  //OBS : la valeur de l'id est automatiquement enregistrée par save() (voir DataObjet)
	$file_size=$_FILES['enveloppe']['size'];
	
  if ($file_size>HELIOS_MAX_UPLOAD_SIZE)
	{
		$_SESSION["error"] = "Taille de fichier supérieur à la limite autorisée (". (HELIOS_MAX_UPLOAD_SIZE/1024/1024)."Mo maximum).";
	  header("Location: " . WEBSITE_SSL);
	  exit ();
	}
	
  $submission_date=date("Y-m-d H:i:s");;
  $ht->set("filename", $uploadFile_baseName);
  $ht->set("user_id", $userId);
  $ht->set("file_size",$file_size);
  $ht->set("submission_date",$submission_date);
  $ht->set("sha1",$SHA1);
  $myAuthority = new Authority($me->get("authority_id"));
  $siren=$myAuthority->get('siren');
  
  //ajouter le ext_siret(5 lettre) dans le nouveau siren.Il faut le renommer comme siret, mais ca va influence du côté java,
  // car il récupère le numéro siren pour former le nom de fichier PES_Aller , donc je le garde mais le sens de SIREN est changé.
  $ext_siret=$myAuthority->get('ext_siret');
  $ht->set("siren",$siren.$ext_siret);
  
 	if ($ht->CheckDuplicate()== true)
 	{
 		unlink($uploadfile);
  	Helpers :: returnAndExit(1, "doublon détecté. Ce fichier a déjà été posté.", WEBSITE_SSL . "/modules/helios/index.php");
 	}
  //change the upload file name to sha1 to allow duplicate name.
  rename($uploadfile,$uploaddir.$SHA1);  
	chmod($uploaddir.$SHA1, 0644);
	
//echo "Teh file_size=$file_size, submission_date=$submission_date, sha1=$SHA1, siren=$siren";
  
//  if ($_FILES['signature']['name'] == null)
//    $ht->set("signed", 'false');
//  else {
//    $ht->set("signed", 'true');
//  }

  $R = $ht->save(true);
  if (!$R) {
    $_SESSION["error"] = "Erreur de l'initialisaton de l'accès à la table helios_transactions.";
    if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
      $_SESSION["error"] .= "\nErreur de journalisation.";
    }
    header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
    echo $_SESSION["error"];
    exit ();
  }
  //recuperation de l'id de la transaction
  $id_transaction = $ht->getId();

  $htw->set("transaction_id", $id_transaction);
  $htw->set("status_id", 1);
  $htw->set("date", date('Y-m-d H:i:s'));
  $htw->set("message", "Fichier bien reçu par la plate-forme Helios");
  if (!$htw->save(true)) {
    $_SESSION["error"] = "Erreur de l'initialisaton de l'accès à la table helios_transactions_workflow.";
    if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
      $_SESSION["error"] .= "\nErreur de journalisation.";
    }
    header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
    echo $_SESSION["error"];
    exit ();
  } else {
    $msg = "Création de la transation n°" . $id_transaction . ". Résultat ok.";
    if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
      $msg .= "\nErreur de journalisation.";
    }
  } 
  Helpers :: returnAndExit(0,"Téléchargement du fichier réussi.", WEBSITE_SSL . "/modules/helios/helios_transac_show.php?id=" . $id_transaction);
}
else
{
     Helpers :: returnAndExit(1, "Echec lors du téléchargement du fichier", WEBSITE_SSL . "/modules/helios/helios_transac_show.php?id=" . $id_transaction);
}
 /* 
  //On regarde, sil la transaction est signée, si la signature est bonne
  // sinon on envoie même pas à la servlet
  $ok_for_servlet = true;
//  if ($_FILES['signature']['name'] != null)
//    $ok_for_servlet = $ht->checksign($uploadfile,$signfile);

 $destination = "http://localhost:8080/tedetis/helios_reception"; 

  //transmission du fichier à la servlet  
  if ($ok_for_servlet) {
    //preparation de donees à envoyer par POST 
    $postData = array ();
    $fileToSend = "@" . $uploaddir.$SHA1;
 //   $signatureToSend = "@" . $signfile;

    //simulates <input type="file" name="...">
    $postData['enveloppe'] = $fileToSend;
 //   if ($_FILES['signature']['name'] != null)
 //     $postData['signature'] = $signatureToSend;
    $postData['submit'] = "UPLOAD";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $destination);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    //seems no need to tell it enctype='multipart/data' it already knows
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    // récuperation du résultat envoyé par servlet : ok ou ko

    $res = curl_exec($ch);
    if (curl_errno($ch))
    {
    	//le fichier n'a pas été envoyé à la servlet (et ne le sera plus)
    	$htw = new HeliosTransactionWorkflow();
    	$htw->set("transaction_id", $id_transaction);
    	$htw->set("status_id", -1);
    	$htw->set("date", date('Y-m-d H:i:s'));
    	$htw->set("message", "Echec du téléchargement");
    	if (!$htw->save(true))
    	{
			    $_SESSION["error"] = "Erreur de l'accès à la table helios_transactions_workflow.";
			    if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
			      $_SESSION["error"] .= "\nErreur de journalisation.";
			    }
			    header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
			    echo $_SESSION["error"];
			    exit ();
			}
			else
			{
			    $msg = "Erreur lors de l'envoi du fichier $id_transaction à la servlet";
			    if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
			      $msg .= "\nErreur de journalisation.";
			    }
	    }
      Helpers :: returnAndExit(1, "tomcat: Echec lors du téléchargement du fichier", WEBSITE_SSL . "/modules/helios/helios_transac_show.php?id=" . $id_transaction);
    }
    else //no erreur ni reception, ni curl...
    {
    		$msg="";
        Helpers :: returnAndExit(0, "Téléchargement du fichier réussi.", WEBSITE_SSL . "/modules/helios/helios_transac_show.php?id=" . $id_transaction);
    }
  }
//   else {
//    $htw = new HeliosTransactionWorkflow();
//    $htw->set("transaction_id", $id_transaction);
//    $htw->set("status_id", -1);
//    $htw->set("date", date('Y-m-d H:i:s'));
//    $htw->set("message", "Signature invalide");
//    if (!$htw->save(true)) {
//      $_SESSION["error"] = "Erreur d'invalidation de la signature de la transaction " . $transaction_id . ".";
//      if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
//        $_SESSION["error"] .= "\nErreur de journalisation de l'invalidité de la signature de la transaction " . $transaction_id . ".";
//      }
//      header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
//      echo $_SESSION["error"];
//      exit ();
//    } else {
//      $msg = "Signature invalide de la transation n°" . $id_transaction;
//      if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 2, false, 'USER', $module->get("name"), $me)) {
//        echo ("Erreur de journalisation de l'invalidité de la signature...");
//      }
//      echo "OK \n" . $id_transaction;
//    }
//  }
} else
{
     Helpers :: returnAndExit(1, "Echec lors du téléchargement du fichier", WEBSITE_SSL . "/modules/helios/helios_transac_show.php?id=" . $id_transaction);
}
*/

