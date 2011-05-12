<?php

require_once( SITEROOT . "class/Versionning.class.php");


class Layout {
  public $header;
  public $body;
  public $title;

  /**
   * \brief Méthode permettant de définir le titre du document
   * \param $str chaîne : le titre du document
  */
  public function setTitle($str) {
    $this->title = $str;
  }

  /**
   * \brief Méthode d'ajout de contenu dans l'en-tête du document
   * \param $str chaîne : chaîne de caractères à ajouter dans le document
  */
  public function addHeader($str) {
    $this->header .= $str;
  }

  /**
   * \brief Méthode d'ajout de contenu dans le corps du document
   * \param $str chaîne : chaîne de caractères à ajouter dans le document
  */
  public function addBody($str) {
    $this->body .= $str;
  }
  /**
   * 
   * @param $templateFile: le template full path name correspond to the controller index.php 
   * @return no return value
   */
  public function setTemplate($template)
  {
  	$this->templateFile=$template;
  }

}

/**
 * \class HTMLLayout Layout.class.php
 * \brief Classe pour la génération de mise en page en HTML
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 17.02.2006
 * 
 *
 * Cette classe fournit des méthodes pour la génération de mise en page en HTML
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

class HTMLLayout extends Layout {

	protected $template = false;
	
	private $errorDisabled;
	
  public function __construct($template = false) {
    if ($template) {
      $this->template = $template;
    } elseif (defined("DEFAULT_HTML_TEMPLATE")) {
      $this->template = DEFAULT_HTML_TEMPLATE;
    }
  }
  
  public function disableError(){
  	$this->errorDisabled = true;
  }

  /**
   * \brief Méthode permettant de construire un menu
   * \param $user objet (optionnel) : objet représentant l'utilisateur en cours pour personnalisation du menu
   * \param $displayInline booléen (optionnel) : spécifie si le HTML doit être affiché (true) ou ajouté au corps du document (false, par défaut)
  */
  public function buildMenu($user = false, $displayInline = false) {
    $html = "<div id=\"menu-area\">\n";
    $html .= "<div id=\"menu\">\n";

    if (! $user) { // Si pas d'utilisateur on se trouve dans la page d'accueil
      $html .= "<div id=\"menu-header\">\n";
      $html .= "<a href=\"" . WEBSITE_SSL . "\">Accéder au site</a><br />\n";
      $html .= "(Certificat nécessaire)";
      $html .= "</div>\n";
    } else { // Personnalisation du menu en fonction du rôle de l'utilisateur
      $html .= "<div id=\"menu-header\">\n";
      $html .= "Bienvenue " . $user->getPrettyName() . "<br />\n";;
      $html .= "Rôle&nbsp;: " . $user->getRoleDescr();
      if ($user->isLogged()){
      	$html .= "<br/><a href='".WEBSITE_SSL."/logout.php'>déconnexion</a>";
      }
      $html .= "</div>\n";
      $html .= "<ul class=\"text-menu\">\n";

	  $modules = Module::getModulesForUser($user->getId());

	  $modHTML = $modHTML = "<li class=\"menu-list-title\">Modules</li>\n";
	  $adminModHTML = "";
	  $statsModHTML = "";
	  if (count($modules) > 0) {
		foreach ($modules as $module) {
		  if ($user->canAccess($module["name"])) {
			$modHTML .= "<li><a href=\"" . WEBSITE_SSL . "/modules/" . $module["name"] . "/\">" . $module["menu_entry"] . "</a></li>\n";
			
			if (file_exists(SITEROOT . "/public.ssl/modules/" . $module["name"] . "/" . $module["name"] . "_stats.php")) {
			  $statsModHTML .= "<li><a href=\"" . WEBSITE_SSL . "/modules/" . $module["name"] . "/" . $module["name"] . "_stats.php\">Statistiques module " . $module["name"] . "</a></li>\n";
			}
		  }
		  if (file_exists(SITEROOT . "/public.ssl/modules/" . $module["name"] . "/admin/index.php")) {
			$adminModHTML .= "<li><a href=\"" . WEBSITE_SSL . "/modules/" . $module["name"] . "/admin/index.php\">Utilitaires module " . $module["name"] . "</a></li>\n";
		  }
		}
	  } else {
		$modHTML .= "<li>Aucun module accessible</li>";
	  }

      switch ($user->get("role")) {
      case 'SADM': // Super Administrateur
		$html .= "<li class=\"menu-list-title\">Administration</li>\n";
		$html .= "<li><a href=\"" . WEBSITE_SSL . "/admin/modules/admin_modules.php\">Gestion des modules</a></li>\n";
		$html .= "<li><a href=\"" . WEBSITE_SSL . "/admin/groups/admin_groups.php\">Gestion des groupes</a></li>\n";
		$html .= "<li><a href=\"" . WEBSITE_SSL . "/admin/authorities/admin_authorities.php\">Gestion des collectivités</a></li>\n";
		$html .= "<li><a href=\"" . WEBSITE_SSL . "/admin/users/admin_users.php\">Gestion des utilisateurs</a></li>\n";
		$html .= "<li><a href=\"" . WEBSITE_SSL . "/admin/services/admin_services.php\">Gestion des services</a></li>\n";
		
		$html .= "<li><a href=\"" . WEBSITE_SSL . "/admin/utilities/index.php\">Utilitaires système</a></li>\n";
		$html .= $adminModHTML;
		$html .= $modHTML;
		$html .= "<li class=\"menu-list-title\">Suivi du site</li>\n";
		$html .= "<li><a href=\"" . WEBSITE_SSL . "/common/logs_view.php\">Journal des événements</a></li>\n";
		$html .= $statsModHTML;

        break;

      case 'GADM': // Administrateur de groupe
		$html .= "<li class=\"menu-list-title\">Administration</li>\n";
		$html .= "<li><a href=\"" . WEBSITE_SSL . "/admin/authorities/admin_authorities.php\">Gestion des collectivités</a></li>\n";
		$html .= "<li><a href=\"" . WEBSITE_SSL . "/admin/users/admin_users.php\">Gestion des utilisateurs</a></li>\n";
		$html .= "<li><a href=\"" . WEBSITE_SSL . "/admin/services/admin_services.php\">Gestion des services</a></li>\n";
		$html .= $adminModHTML;
		$html .= $modHTML;
		$html .= "<li class=\"menu-list-title\">Suivi du site</li>\n";
		$html .= "<li><a href=\"" . WEBSITE_SSL . "/common/logs_view.php\">Journal des événements</a></li>\n";
		$html .= $statsModHTML;
        break;

      case 'ADM': // Administrateur collectivité
		$html .= "<li class=\"menu-list-title\">Administration</li>\n";
		$html .= "<li><a href=\"" . WEBSITE_SSL . "/modules/mail/index.php?command=annuaire\">Carnet d'adresses de la collectivité</a></li>\n";
		$html .= "<li><a href=\"" . WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php?id=" . $user->get("authority_id") . "\">Paramètres collectivité</a></li>\n";
		$html .= "<li><a href=\"" . WEBSITE_SSL . "/admin/users/admin_users.php\">Gestion des utilisateurs</a></li>\n";
		$html .= "<li><a href=\"" . WEBSITE_SSL . "/admin/services/admin_services.php\">Gestion des services</a></li>\n";
		$html .= $modHTML;
		$html .= "<li class=\"menu-list-title\">Suivi du site</li>\n";
		$html .= "<li><a href=\"" . WEBSITE_SSL . "/common/logs_view.php\">Journal des événements</a></li>\n";
		$html .= $statsModHTML;
        break;

      case 'USER': // Utilisateur simple
		$html .= $modHTML;
		$html .= "<li class=\"menu-list-title\">Suivi</li>\n";
		$html .= "<li><a href=\"" . WEBSITE_SSL . "/common/logs_view.php\">Journal des événements</a></li>\n";
		$html .= $statsModHTML;

		break;
      }
      
      $html .= "</ul>\n";
    }

    $html .= "</div>\n";
    $html .= "</div>\n";
    
    if ($displayInline) {
      echo $html;
    } else {
      $this->addBody($html);
      //$this->includeErrors();
    }
  }


  /**
   * \brief Méthode de construction du pied de page du document
   * \param $displayInline booléen (optionnel) : spécifie si le HTML doit être affiché (true) ou ajouté au corps du document (false, par défaut)
  */
  public function buildFooter($displayInline = false) {
  	
    $html = "<div id=\"footer\">\n";

	if (defined("WEBMASTER")) {
	  $html .= "<a href=\"mailto:" . WEBMASTER . "\" class=\"link-white\">Webmaster</a> - ";
	}

	if (defined("SUPPORT_URL")) {
	  $html .= "<a href=\"" . SUPPORT_URL . "\" class=\"link-white\">Support</a> - ";
	}

	$versionning = VersionningFactory::getInstance();
	$versionningInfo = $versionning->getAllInfo();
	
	$html .= "Offre S²LOW - <a href=\"" . WEBSITE_SSL . "/common/release_notes.php\">".
  	$versionningInfo['version-complete'] . "</a>\n";


	
	$html .= "</div>\n";

    if ($displayInline) {
      echo $html;
    } else {
      $this->addBody($html);
    }
  }

  /**
   * \brief Méthode de construction de la zone de pagination
   * \param $dataObj DataObject : objet représentant les données manipulées et contenant les informations de pagination
   * \param $displayInline booléen (optionnel) : spécifie si le HTML doit être affiché (true) ou ajouté au corps du document (false, par défaut)
  */
  public function buildPager($dataObj, $displayInline = false) {
	$html = "<div id=\"pager\">\n";
	$html .= "<h1>Pagination</h1>\n";

	// Nombre de résultats par page
	$html .= "<h2>Afficher par page&nbsp;:</h2>\n";
	$html .= "<div class=\"links_area\">\n";

	foreach (array(10, 20, 50, 100) as $val) {
	  if ($dataObj->get("displayItems") != $val) {
		$html .= "<a href=\"" . Helpers::getURLWithParam(array("count" => $val)) . "\" title=\"Afficher " . $val . " éléments par page\">" . $val . "</a>\n";
	  } else {
		$html .= "&nbsp;" . $val . "&nbsp;";
	  }
	}

	$html .= "</div>\n";

	// Liste des pages
	$html .= "<h2>Page&nbsp;:</h2>\n";

	$args = preg_replace("/&?page=[0-9]+/", "", $_SERVER["QUERY_STRING"]);
	$args = preg_replace("/^&/", "", $args);
	$args = preg_replace("/&/", "&amp;", $args);
	$sep = (strlen($args) > 0) ? "&amp;" : "";

	$html .= "<div class=\"links_area\">\n";
	for ($i = 1; $i <= $dataObj->get("pageNbr"); $i++) {
	  if ($dataObj->get("currentPage") == $i) {
		$html .= "&nbsp;" . $i . "&nbsp;";
	  } else {
		$html .= "<a href=\"" . Helpers::getURLWithParam(array("page" => $i)) . "\" title=\"Afficher la page " . $i . "\">" . $i . "</a>\n";
	  }
	}

	$html .= "</div>\n";

	// Liens suivant/précédent
	$html .= "<div class=\"links_area\">\n";

	if ($dataObj->get("currentPage") > 1) {
	  $args = preg_replace("/&?page=[0-9]+/", "", $_SERVER["QUERY_STRING"]);
	  $args = preg_replace("/^&/", "", $args);
	  $args = preg_replace("/&/", "&amp;", $args);
	  $sep = (strlen($args) > 0) ? "&amp;" : "";

	  $html .= "<a href=\"" . Helpers::getURLWithParam(array("page" => ($dataObj->get("currentPage") - 1))) . "\" title=\"Afficher la page précédente\"><<<</a>\n";
	} else {
	  $html .= "<<<";
	}

	$html .= "&nbsp;|&nbsp;";

	if ($dataObj->get("currentPage") < $dataObj->get("pageNbr")) {
	  $args = preg_replace("/&?page=[0-9]+/", "", $_SERVER["QUERY_STRING"]);
	  $args = preg_replace("/^&/", "", $args);
	  $args = preg_replace("/&/", "&amp;", $args);
	  $sep = (strlen($args) > 0) ? "&amp;" : "";

	  $html .= "<a href=\"" . Helpers::getURLWithParam(array("page" => ($dataObj->get("currentPage") + 1))) . "\" title=\"Afficher la page suivante\">>>></a>\n";
	} else {
	  $html .= ">>>";
	}


	$html .= "</div>\n";
    $html .= "</div>\n";

    if ($displayInline) {
      echo $html;
    } else {
      $this->addBody($html);
    }
  }


  /**
   * \brief Méthode de construction d'un champ de formulaire de type select
   * \param $name chaîne : Nom du champ de formulaire
   * \param $data tableau : tableau ayant pour clef les valeurs et pour valeur les noms des entrées du select
   * \param $selectedValue mixed : valeur actuelle du champ pour préselection
   * \param $extraAttributes chaîne : chaîne de caractères contenant des attribut HTML à ajouter au select
   * \return Le code HTML du champ select
  */
  public function getHTMLSelect($name, $data, $selectedValue, $extraAttributes = "",$onChange=null) {
  	if ($onChange==null)
    	$html = "<select name=\"" . $name . "\"" . $extraAttributes . ">\n";
    else 
    	$html = '<select id="'.$name.'" name="' . $name . "\"" . $extraAttributes . ' onchange="'.$onChange.'">\n';
    $html .= " <option value=\"\">Choisissez</option>\n";

    foreach ($data as $key => $val) {
      $html .= " <option value=\"" . $key . "\"";

      $html .= (strcmp($key, $selectedValue) == 0) ? " selected=\"selected\"" : "";
      
      $html .= ">" . $val . "</option>\n";
    }

    $html .= "</select>\n";

    return $html;
  }
  

  /**
   * \brief Méthode de construction d'un champ de formulaire de type checkbox
   * \param $name chaîne : Nom du champ de formulaire
   * \param $value mixed : Valeur du champ, si évalué à "true" la checkbox sera précochée
   * \param $extraAttributes chaîne : Attributs supplémentaires du champ HTML
   * \return Le code HTML du champ checkbox
  */
  public function getHTMLCheckbox($name, $value, $extraAttributes = "") {
    $html = "<input type=\"checkbox\" name=\"" . $name . "\" value=\"on\"";

    if ($value == "on") {
      $html .= " checked=\"checked\"";
    }

    $html .= $extraAttributes . " />";

    return $html;
  }

  /**
   * \brief Méthode de construction d'une ligne de tableau de liste d'attribut
   * \param $name chaîne : Nom de l'attribut
   * \param $value mixed : Valeur de l'attribut
   * \return Le code HTML de la ligne
  */
  public function getHTMLArrayline($name, $value) {
	$html = " <tr>\n";
	$html .= "  <td class=\"td-register\">" . $name . "&nbsp;:</td>\n";
	$html .= "  <td class=\"td-input\">" . $value . "</td>\n";
	$html .= " </tr>\n";

    return $html;
  }


  /**
   * \brief Méthode d'inclusion du message d'erreur stocké en session
  */
	public function includeErrors() {		
		if ($this->errorDisabled){
			return;
		}
		ob_start();
		$this->afficheErrors($_SESSION["error"]);
		$html = ob_get_contents();
		ob_end_clean();
		//$this->addBody($html);
		// Gros hack moisi à cause d'IE qui bug à l'affichage
		// il faut "injecter" la zone d'erreur à l'intérieur de la zone "content"
		//FIXME (EP), ce n'est pas un "bug" d'IE, la CSS ne défini la errorbox qu'a l'interieur du content
		//FIXME c'est cette classe qui n'est pas très bien concu ...
		$this->body = str_replace("<div id=\"content\">", "<div id=\"content\">\n" . $html, $this->body);		
	}
  
	public function afficheErrors(){ 
		if (! isset($_SESSION["error"]) || ! $_SESSION["error"] ) {
			return;
		}
		?>
		<div id="errorbox" style="display: block;">
			<div id="close_button_area">
				<a href="#close" onclick="javascript:toggle_visibility('errorbox');">
					<img src="<?php  echo WEBSITE_SSL ?>/custom/images/close_button.png" title="Masquer les messages" alt="close_icon" />
				</a>
			</div>
			<?php echo $_SESSION["error"]; ?>
		</div>  	
	<?php
		unset($_SESSION["error"]); 
	}

  
	public function displayTemplate($layout,$templateFile)
	{
		$this->includeErrors();
		require_once(HTML_TEMPLATE_PATH . "/" . "new.generic.tpl.php");
	}
  /**
   * \brief Méthode générant l'affichage du document
  */
 
  public function display() {
    $this->includeErrors();

    if ($this->template) {
      require_once(HTML_TEMPLATE_PATH . "/" . $this->template);
    } else {
      echo "<?xml version=\"1.0\" encoding=\"iso-8859-15\"?>\n";
      echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n";
      echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"fr\">\n";
      echo "<head>\n";
      echo "<title>" . $this->title . "</title>\n";
      echo $this->header . "\n";
      echo "</head>\n";
      echo "<body>\n";
      echo $this->body . "\n";
      echo "</body>\n";
    }
  }
}

/**
 * \class CSVLayout Layout.class.php
 * \brief Classe pour la génération de fichier CSV
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 17.02.2006
 * 
 *
 * Cette classe fournit des méthodes pour la génération de fichiers CSV
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

class CSVLayout extends Layout {

  /**
   * \brief Méthode d'ajout d'une ligne dans le fichier CSV
   * \param $str chaîne : chaîne de caractères à ajouter dans le document ou tableau de champs qui seront ajoutés séparés par des points virgules
  */
  public function addLine($str) {
	if (is_array($str)) {
	  $line = implode(";", $str);
	} else {
	  $line = $str;
	}

	$line .= "\r\n";

	$this->addBody($line);
  }

  /**
   * \brief Méthode générant l'affichage du document
   */
  public function display() {
	$content_type = "text/csv;charset=iso-8859-1";
	if (! empty($this->header)) {
	  $content_type .= ";header=present";
	}

	if (! Helpers::sendFileToBrowser(null, "transactions.csv", $content_type)) {
	  return false;
	}

	if (! empty($this->header)) {
	  echo $this->header . "\r\n";
	}

	echo $this->body;
  }
}

?>
