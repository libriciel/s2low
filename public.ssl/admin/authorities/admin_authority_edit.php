<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, AoÃ»t 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant Ã  la
 * dÃ©matÃ©rialisation de l'administration. 
 *
 * Ce logiciel est rÃ©gi par la licence CeCILL soumise au droit franÃ§ais et
 * respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusÃ©e par le CEA, le CNRS et l'INRIA 
 * sur le site "http://www.cecill.info".
 *
 * En contrepartie de l'accessibilitÃ© au code source et des droits de copie,
 * de modification et de redistribution accordÃ©s par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitÃ©e.  Pour les mÃªmes raisons,
 * seule une responsabilitÃ© restreinte pÃ¨se sur l'auteur du programme,  le
 * titulaire des droits patrimoniaux et les concÃ©dants successifs.
 *
 * A cet Ã©gard  l'attention de l'utilisateur est attirÃ©e sur les risques
 * associÃ©s au chargement,  Ã  l'utilisation,  Ã  la modification et/ou au
 * dÃ©veloppement et Ã  la reproduction du logiciel par l'utilisateur Ã©tant 
 * donnÃ© sa spÃ©cificitÃ© de logiciel libre, qui peut le rendre complexe Ã  
 * manipuler et qui le rÃ©serve donc Ã  des dÃ©veloppeurs et des professionnels
 * avertis possÃ©dant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invitÃ©s Ã  charger  et  tester  l'adÃ©quation  du
 * logiciel Ã  leurs besoins dans des conditions permettant d'assurer la
 * sÃ©curitÃ© de leurs systÃ¨mes et ou de leurs donnÃ©es et, plus gÃ©nÃ©ralement, 
 * Ã l'utiliser et l'exploiter dans les mÃªmes conditions de sÃ©curitÃ©. 
 *
 * Le fait que vous puissiez accÃ©der Ã  cet en-tÃªte signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez acceptÃ© les
 * termes.
*/
?>
<?php
/**
 * \file admin_authority_edit.php
 * \brief Page de modification ou d'ajout d'une collectivité
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 17.03.2006
 * 
 *
 * Cette page affiche un formulaire permettant d'ajouter ou de modifier
 * une collectivité.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *  JS   19.07.2006  Adaptation pour Tedetis
 */

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isAdmin()) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$id = Helpers::getVarFromGet("id");

// Mode modification ou pas
$mod = false;
$authority = new Authority();

$modStr = "Ajout";
if (isset($id)) {
  $authority->setId($id);
  if ($authority->init()) {
    $modStr = "Modification";
    $mod = true;
  } else {
    $authority = new Authority();
  }
}

if (! $mod && ! $me->isGroupAdminOrSuper()) {
  $_SESSION["error"] = "Accès refusé.";
  header("Location: " . WEBSITE_SSL);
  exit();
}

// Vérification permission sur la collectivité
if (($mod && $me->isGroupAdmin() && ! $authority->isInGroup($me->get("authority_group_id"))) || ($me->isAuthorityAdmin() && $authority->getId() != $me->get("authority_id"))) {
  $_SESSION["error"] = "Accès refusé pour la modification de cette collectivité";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$doc = new HTMLLayout();

$doc->addHeader("<script src=\"" . WEBSITE_SSL . "/javascript/validateform.js\" type=\"text/javascript\"></script>\n");

$doc->setTitle("Tedetis : " . $modStr . " collectivité");

$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<h1>Gestion collectivités";

if ($me->isGroupAdmin()) {
  $myGroup = new Group($me->get("authority_group_id"));
  $html .= " du groupe " . htmlspecialchars($myGroup->get("name"));
}

$html .= "</h1>\n";

if ($me->isGroupAdminOrSuper()) {
  $html .= "<center><a href=\"" . WEBSITE_SSL . "/admin/authorities/admin_authorities.php\" class=\"bouton\">Retour liste collectivités</a></center>\n";
}

$html .= "<h2>" . $modStr . " collectivité</h2>\n";
$html .= "<form action=\"" . WEBSITE_SSL . "/admin/authorities/admin_authority_edit_handler.php\" method=\"post\" name=\"form\" onsubmit=\"javascript:return validateForm(" . $authority->getValidationTrio('name', 'siren', 'agreement', 'email', 'broadcast_email', 'status', 'authority_type_id', 'address', 'postal_code', 'city', 'department', 'district', 'telephone', 'fax') . ")\">\n";

if ($mod) {
  $html .= "<input type=\"hidden\" name=\"id\" value=\"" . $authority->getId() . "\" />\n";
  $html .= "<input type=\"hidden\" name=\"mode\" value=\"modify\" />\n";
} else {
  $html .= "<input type=\"hidden\" name=\"mode\" value=\"create\" />\n";
}

$html .= "<div class=\"data_table\">\n";
$html .= "<table style=\"width: 100%\">\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Nom&nbsp;:</td>\n";

if ($me->isGroupAdminOrSuper()) {
  $html .= "  <td class=\"td-input\"><input type=\"text\" name=\"name\" value=\"" . htmlspecialchars($authority->get("name")) . "\" size=\"30\" maxlength=\"60\" /></td>\n";
} else {
  $html .= "<td class=\"td-input\">" . htmlspecialchars($authority->get("name")) . "</td>\n";
}
 
$html .= " </tr>\n";

//TODO: le mot de passe n'est pas caché, il faut soit faire un md5 sur le mot de passe et ici on affiche pas mot de passe. quand il chnange on le cripte par md5 et le sauvgarder dans base de donné.
//en ce moment je pas le temp de tout faire et je laiss pour après.
//********************************
$accessHelios=0;


//TODO: si le collectivité en éditer n'as pas le droit d'access de helios ;il faut pas le affichier.


		
if ($authority->getModulePermByName("helios"))
{
	$html .=" <tr>\n";
	$html .= "  <td class=\"td-register\">HELIOS ftp login&nbsp;:</td>\n";
	$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"helios_ftp_login\" value=\"" . htmlspecialchars($authority->get("helios_ftp_login")) . "\" size=\"15\" maxlength=\"30\" /></td>\n";
	$html .=" </tr>\n";
	
	$html .=" <tr>\n";
	$html .= "  <td class=\"td-register\">HELIOS ftp mot de passe&nbsp;:</td>\n";
	$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"helios_ftp_password\" value=\"" . htmlspecialchars($authority->get("helios_ftp_password")) . "\" size=\"15\" maxlength=\"30\" /></td>\n";
	$html .=" </tr>\n";
	
	$html .=" <tr>\n";
	$html .= "  <td class=\"td-register\">HELIOS ftp Dest&nbsp;:</td>\n";
	$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"helios_ftp_dest\" value=\"" . htmlspecialchars($authority->get("helios_ftp_dest")) . "\" size=\"15\" maxlength=\"30\" /></td>\n";
	$html .=" </tr>\n";
	
	$html .=" <tr>\n";
	$html .= "  <td class=\"td-register\">Numéro de EXT SIRET&nbsp;:</td>\n";
	$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"ext_siret\" value=\"" . htmlspecialchars($authority->get("ext_siret")) . "\" size=\"15\" maxlength=\"15\" /></td>\n";
	$html .=" </tr>\n";
}
//********************************

	if ($me->isSuper()) {
  $groups = Group::getGroupsIdName();

  foreach($groups as $key =>$value)
  {
  	  $group = new Group($key);
  		$sirenList[] = $group->getAuthorizedSiren();
  		$groupIds[]=$key;
  }
  
    $valueString   =  "";  
    $indexString = "";
    $indexString='"'.join('","',$groupIds).'"';
 	foreach($sirenList   as  $value)   {  
      if(is_array($value))  
          $valueString   .=   (!empty($valueString)?",":"").'new   Array("'.join('","',$value).'")';  
      else  
          $valueString   .=   '"'.$value.'"';  
  }   
	?>
	<script	language="JavaScript">
	

	function groupchange()
	{
		var   sirenArray   =   new   Array(<?php   echo   $valueString;   ?> );  
		var   groupIdArray = new Array(<?php echo $indexString ;?> );
		var groupId=document.getElementById("authority_group_id");
		var sienSelect=document.getElementById("sirenId");
		for (var i=0;i< groupIdArray.length;i++)
		{
			if (groupIdArray[i]==groupId.value)
			{ 
				var siren=sirenArray[i];
				sienSelect.options.length = 0; 
				for(var j = 0; j < siren.length; j++) {
	
					sienSelect.options[j]=new Option(siren[j],siren[j]); 		
				}
				break;	
			}
		}
	}
	
	</script>
	<?php 
  $html .= " <tr>\n";
  $html .= "  <td class=\"td-register\">Groupe&nbsp;:</td>\n";
  $html .= "  <td class=\"td-input\">\n";
  $html .= $doc->getHTMLSelect("authority_group_id", $groups, $authority->get("authority_group_id"),"","groupchange()");
  $html .= "  </td>\n";
  $html .= " </tr>\n";
}
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Numéro de SIREN&nbsp;:</td>\n";

if ($me->isGroupAdminOrSuper())
{
		
	if ($modStr=="Ajout")
	{
		$group=new Group($me->get('authority_group_id'));
	}
	else
		$group = new Group($authority->get("authority_group_id"));  
	$sirenList=$group->getAuthorizedSiren();
  $html .= "  <td class=\"td-input\"><select id=\"sirenId\" name=\"siren\">";
   foreach ($sirenList as $siren_tmp)
  {
  	if ($siren_tmp == $authority->get("siren"))
  	{
 	 	$html.=" <option value =\"$siren_tmp\" selected=\"selected\">$siren_tmp</option>";
  	}
  	else 
  	{
  		$html.=" <option value =\"$siren_tmp\" >$siren_tmp</option>";
  	}
  }
  $html.=" </select></td>\n";
} else {
  $html .= "<td class=\"td-input\">" . htmlspecialchars($authority->get("siren")) . "</td>\n";
}

$html .= " </tr>\n";

//************


$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Type de collectivité&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\">\n";

if ($me->isGroupAdminOrSuper()) {
  $html .= "  <input type=\"hidden\" id=\"authority_type\" name=\"authority_type_id\" value=\"" . $authority->get("authority_type_id") . "\" />\n";
  $html .= "  <a class=\"link_white\" href=\"#tedetis\" onclick=\"javascript:window.open('" . WEBSITE_SSL . "/common/select_popup.php?type=authority_type', 'Select_attribut', 'location=no,scrollbars=yes,menubar=no,status=no,toolbar=no,directories=no,width=512,height=560');\" id=\"authority_type_text\">";

  if ($authority->get("authority_type_id")) {
	$html .= $authority->getAuthorityTypeName();
  } else {
	$html .= "[&nbsp;Choisir un type&nbsp;]";
  }
  $html .= "</a>\n";
} else {
  $html .= $authority->getAuthorityTypeName();
}

$html .= " </td>\n";
$html .= " </tr>\n";

if ($me->isGroupAdminOrSuper()) {
  $html .= " <tr>\n";
  $html .= "  <td class=\"td-register\">État&nbsp;:</td>\n";
  $html .= "  <td class=\"td-input\">\n";

  $html .= $doc->getHTMLSelect("status", $authority->get("statusTypes"), $authority->get("status"));
  
  $html .= "  </td>\n";
  $html .= " </tr>\n";
}

$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Adresse électronique «&nbsp;métier&nbsp;»&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"email\" value=\"" . htmlspecialchars($authority->get("email")) . "\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Adresse électronique de diffusion par défaut&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"default_broadcast_email\" value=\"" . htmlspecialchars($authority->get("default_broadcast_email")) . "\" size=\"30\" maxlength=\"600\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Adresse électronique de diffusion d'informations&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"broadcast_email\" value=\"" . htmlspecialchars($authority->get("broadcast_email")) . "\" size=\"30\" maxlength=\"600\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Adresse&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\">\n";
$html .= "   <textarea name=\"address\">" . htmlspecialchars($authority->get("address")) . "</textarea>\n";
$html .= "  </td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Code postal&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"postal_code\" value=\"" . htmlspecialchars($authority->get("postal_code")) . "\" size=\"30\" maxlength=\"20\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Ville&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"city\" value=\"" . htmlspecialchars($authority->get("city")) . "\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Département&nbsp;/&nbsp;Arrondissement&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\">";

if ($me->isGroupAdminOrSuper()) {
  $html .= "  <input type=\"hidden\" id=\"department\" name=\"department\" value=\"" . $authority->get("department") . "\" />\n";
  $html .= "  <input type=\"hidden\" id=\"district\" name=\"district\" value=\"" . $authority->get("district") . "\" />\n";
  $html .= "  <a class=\"link_white\"href=\"#tedetis\" onclick=\"javascript:window.open('" . WEBSITE_SSL . "/common/select_popup.php?type=department', 'Select_attribut', 'location=no,scrollbars=yes,menubar=no,status=no,toolbar=no,directories=no,width=300,height=560');\" id=\"department_text\">";

  if ($authority->getDeptDistrString()) {
	$html .= $authority->getDeptDistrString();
  } else {
	$html .= "[&nbsp;Choisir un département/arrondissement&nbsp;]";
  }
  $html .= "</a>\n";
} else {
  $html .= $authority->getDeptDistrString();
}

$html .= "  </td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Téléphone&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"telephone\" value=\"" . htmlspecialchars($authority->get("telephone")) . "\" size=\"30\" maxlength=\"20\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Fax&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"fax\" value=\"" . htmlspecialchars($authority->get("fax")) . "\" size=\"30\" maxlength=\"20\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Adresse électronique pour le module de mail sécurisé:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"email_mail_securise\" value=\"" . htmlspecialchars($authority->get("email_mail_securise")) . "\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
if ($me->isGroupAdminOrSuper()) {
	$modules = Module::getActiveModulesList();

  $html .= " <tr>\n";
  $html .= "  <td class=\"td-register\">Modules autorisés&nbsp;:</td>\n";
  $html .= "  <td class=\"td-input\">";

  //$me->canGrantModule($module["name"]
  foreach ($modules as $module) {
	if ($me->isGroupAdminOrSuper()){
	  $html .= "<label>" . $doc->getHTMLCheckbox("perm_" . $module["id"], $authority->getModulePerm($module["id"]));
	  $html .= "&nbsp;" . $module["description"] . "</label><br />\n";
	}
  }

  $html .= "  </td>\n";
  $html .= " </tr>\n";
}

$html .= "</table>\n";
$html .= "</div>\n";
$html .= "<center><input type=\"submit\" class=\"submit_button\" value=\"";
$html .= ($mod) ? "Valider les modifications" : "Ajouter la collectivité";
$html .= "\" /></center>\n";
$html .= "</form>\n";

/*if ($mod && $me->isSuper()) {
  $html .= "<form action=\"" . WEBSITE_SSL . "/admin/authorities/admin_authority_delete.php\" onsubmit=\"return confirm('Voulez-vous vraiment supprimer définitivement cette collectivité ?')\" method=\"post\">\n";
  $html .= "<input type=\"hidden\" name=\"id\" value=\"" . $authority->getId(). "\" />\n";
  $html .= "<input type=\"submit\" value=\"Supprimer cette collectivité\" class=\"bouton-danger\" />\n";
  $html .= "</form>\n";
}*/

$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();

