<?php

//reception d'un fichier par POST HTTP et son "forward" vers le servlet
//si tout est okay => Response: "ok \n"
//si un erreur de reception/validation => Reponse: "ko \n"
//author: C. Pop
//17.01.2007


  
// Configuration
require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
//à inclure les classes dont j'ai besoin...'
require_once (SITEROOT . '/public.ssl/modules/etat_civil/class/etat_civilTransaction.class.php');
require_once (SITEROOT . '/public.ssl/modules/etat_civil/class/etat_civilTransactionWorkflow.class.php');
require_once (SITEROOT . '/class/User.class.php');

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("etat_civil")) {
  $_SESSION["error"] = "Erreur d'initialisation du module";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$me = new User();
//l'utilisateur'

if (!$me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit ();
}
//echo "OK authentification <br>";

///recuperation de données sur moi-mêmee ;)
$nomUSer=$me->get("name");//?!...
$userId=$me->getId();

//echo "ME: ".$nomUser." ID=".$userId."<br>";

if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  echo $_SESSION["error"];
  exit ();
}
// echo "OK activation du module <br>";
  
  //reception du fichier xml
  //***********************************************************************
  //TODO cela dans la configuration!!!...    
     $tmp="/tmp/";//"/tdt-workspace/etat_civil/";
     $destination="http://localhost:8080/etat_civil/reception";
     
     $ok=0; $ko=0;
     //ME  daca fisierul a fost bine trimis la servlet=> testez valorile: daca ko ==1 => trimite raspuns de eroare....
     //recuperation du fichier sous son vrai nom


     $uploaddir =$tmp;
     
     $uploadFile_baseName=$_FILES['enveloppe']['name']; 
  //   echo "BASE NAME: ".basename($_FILES['enveloppe']['name'])."<br>";
     $uploadfile = $uploaddir . basename($_FILES['enveloppe']['name']);
    
  //   echo "UPLOADFILE ".$uploadfile."<br>"."TMP: ".$tmp."<br>"; 

     //un premier teste que le fichier a bien été recu    
     if (strcmp($uploadfile,$tmp)==0){
        echo "<br> ko recuperation du fichier\n";
        exit;    
     }
    
 //tmp
// echo "<br> ****** FILE: ".$uploadfile."<br>";

     
//echo 'Voici quelques informations de débogage :';
//echo "<br> ScriptRec: saucisson recu: ";
//print_r($_FILES);
    //********************************************************************************//

     if (move_uploaded_file($_FILES['enveloppe']['tmp_name'], $uploadfile)) {

	  //enregistrer dans la BD le fichier avec le status "POSTE"	
	  /***********************************************************************************/
 //     echo "Acces à la BD <br>";
	 
	  //recuperation du nom de proprietaire du fichier posté..
	  //instances de 2 tables impliqué
	  
      $ht = new etat_civilTransaction();
      $htw = new etat_civilTransactionWorkflow();
 
//echo "Instance d'acces aux BD:".$ht.":".$htw."<br>";
  
          //insertion (idUSer, filename) dans la table etat_civil_transactions  => un id de la transaction
          // où filename = le nom du fichier inclut dans le fichier message 
      	  //OBS : la valeur de l'id est automatiquement enregistré par save() (à voir DataObjet)
//echo " & ".$userId."...<br>"; 
      	  $ht-> set("filename",$uploadFile_baseName);
      	  $ht-> set("user_id",$userId);
      	  $R=$ht->save(false);//obs save sans validation 
//echo "REz de save: ".$R."<br>";
//echo "Probleme ".$ht->getErrorMsg()." !! <br>";     	  
      	  if (!$R){
    	  	  $_SESSION["error"] = "Erreur de l'initialisaton de l'accès à la table etat_civil_transactions.";
	          header("Location: " . WEBSITE_SSL . "/modules/etat_civil/index.php");
//echo "Erreur de l'initialisaton de l'accès à la table etat_civil_transactions.";
	          exit();
      	  }
//echo "Ok: inregistrarea in BD etat_civilTrans.<br>";
          //recuperation de l'id de la transaction
	       $id_transaction = $ht-> getId();
//echo "ID transactie:".$id_transaction."<br>";
        //insertion (transaction_id,"POSTE",'date',msg) dans la table etat_civil_transactions_workflows
         $htw->set("transaction_id",$id_transaction);
         $htw->set("status_id",1);//POSTE         
         $htw->set("date",date('Y-m-d H:i:s'));     
         $htw->set("message","Fichier bien réçu à la plate-forme etat_civil");
         if (!$htw->save(false)){          	
        	 $_SESSION["error"] = "Erreur de l'initialisaton de l'accès à la table etat_civil_transactions_workflow.";
	         header("Location: " . WEBSITE_SSL . "/modules/etat_civil/index.php");
echo "Erreur de l'initialisaton de l'accès à la table etat_civil_transactions_workflow.";	     
	         exit();
         }  
//echo "Bien enregistré en transactionWorkflow...<br>";      
      //tmp 
      //1. determine id d'user à partir de son nom (SON NOM?!)
      //   $user_id=$u.get("name"); 
      //2.  $ht.set("filename",$uploadfile);
      //   $ht.set("user_id",user-id); $ht.save(); Id_transaction=ht.getID();
      //3. htw.set("transaction_id",Id_transaction); htw.set("status","POSTE"); 
      //   htw.set("message","Le fichier ".$uploadfile." a bien été reçu à la plate-forme);
      //   htw.set("date",date("Y-m-d H:i:s"); $htw.save(); 
   
		
	  /********************************************************************************/	
		
      //transmission du fichier au servlet  
      //*********************************************************************************        
        //preparation de donees à envoyer par POST
 
 //echo "Partea cu servletul... Start!<br>";
//echo "var: ".$uploadfile." dest:".$destination."<br>";
         
       //preparation de donees à envoyer par POST 
     	$postData = array();
        $fileToSend="@".$uploadfile;

        //simulates <input type="file" name="file_name">
        $postData[ 'enveloppe' ] = $fileToSend;
        $postData[ 'submit' ] = "UPLOAD";

        $ch = curl_init();
//echo "CURL_INIT: ".$ch."<br>";        
        curl_setopt($ch, CURLOPT_URL, $destination );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST, 1 );
        //seems no need to tell it enctype='multipart/data' it already knows
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData );
//pour récuperer le résultat envoyé par servlet : ok ou ko
//BUG: pour le moment le servlet semble que ne valide pas les fichiers xml... :(
//TO WORK: on combine le resultat envoyé par le servlet avec le "ok" du curl...
	$res=curl_exec($ch);
//echo "Php Rezultat recu: ".$res."<br>";
         //si erreur curl
 //REponse en fonction de validation xsd...!!!!!!!
	 if (curl_errno($ch)) {
      		 echo "KO \n";
	 }else{ //no erreur ni reception, ni curl...
       		echo "OK \n".$id_transaction;
 	 }
      } else { //erreur à la reception du fichier...
		echo "KO \n";
      }

  
         
 /*        
	    $postData = array();
        $fileToSend="@".$uploadfile;

        //simulates <input type="file" name="file_name">
        $postData[ 'enveloppe' ] = $fileToSend;
        $postData[ 'submit' ] = "UPLOAD";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $destination );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST, 1 );
        //seems no need to tell it enctype='multipart/data' it already knows
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData );
        
        //$res: pour récuperer le résultat envoyé par servlet : ok ou ko
//BUG: pour le moment il semble que le servlet ne valide pas le fichier xml... :(

     	$res=curl_exec($ch);

//tmp
echo "Rezultat: ".$res." !!<br>";
        
        //Reponse en fonction de la reception du fichier & la validation xsd...
	    if ((!curl_errno($ch)) and (!strcmp($res,"ok"))) {
      		    echo "ok \n";
	    }else{ //soit erreur à la reception du fichier, soit fichier invalide...
       	    	echo "ko fichier invalide \n";
 	    }
      } else { //erreur à la reception du fichier...
		echo "Erreur reception du fichier ko \n";
      }
*/
?>


