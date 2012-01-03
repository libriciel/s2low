<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 *
 * contact@alternancesoft.com
 *
  * Ce logiciel est un programme informatique servant à la
 * dématérialisation de l'administration. 
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

require_once(SITEROOT . "/class/Mailer.class.php");

function checkEmail($email,$antispam = false)
{

	if (!$email || !preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+([\.][a-z0-9-]+)+$/i",$email)){	
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
    $mails=explode(",",$emailtext);  	
    foreach ($mails as $mail)
    {
     	$mail = str_replace ("[","<", $mail); 
		$mail = str_replace("]",">",$mail);		
    	if ($mail!=null)
    	
    		if ($mailer->isValidMail($mail)==false)
    			return false;
    }
    return true;
}
?>