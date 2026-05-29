<?php

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

namespace S2lowLegacy\Class;

use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\ModuleSQL;
use S2lowLegacy\Model\UserSQL;

class HTMLLayout extends Layout
{
    protected $template = false;

    private $errorDisabled;


    public function __construct($template = false)
    {
        if ($template) {
            $this->template = $template;
        } elseif (defined("DEFAULT_HTML_TEMPLATE")) {
            $this->template = DEFAULT_HTML_TEMPLATE;
        }
    }

    public function disableError()
    {
        $this->errorDisabled = true;
    }


    public function openContainer($displayInline = false)
    {
        $html = "        <div class=\"container\">\n";
        $html .= "            <div class=\"row\">\n";
        if ($displayInline) {
            echo $html;
        } else {
            $this->addBody($html);
        }
    }

    public function closeContainer($displayInline = false)
    {
        $html = "            </div><!-- <div class=\"row\" -->\n";
        $html .= "        </div><!-- <div class=\"container\" -->\n";
        if ($displayInline) {
            echo $html;
        } else {
            $this->addBody($html);
        }
    }

    public function openSideBar($displayInline = false)
    {
        $html = "                <div id=\"sidebar\" class=\"col-md-3\" role=\"navigation\">\n";
        if ($displayInline) {
            echo $html;
        } else {
            $this->addBody($html);
        }
    }

    public function closeSideBar($displayInline = false)
    {
        $html = "                </div><!-- <div id=\"sidebar\" -->\n";
        if ($displayInline) {
            echo $html;
        } else {
            $this->addBody($html);
        }
    }

    public function openContent($displayInline = false)
    {
        $html = "                <div id=\"content\" class=\"col-md-9\" role=\"main\">\n";
        if ($displayInline) {
            echo $html;
        } else {
            $this->addBody($html);
        }
    }

    public function closeContent($displayInline = false)
    {
        $html = "                </div><!-- <div class=\"content\" -->\n";

        if ($displayInline) {
            echo $html;
        } else {
            $this->addBody($html);
        }
    }

    /**
     * \brief Méthode permettant de construire un menu
     * \param $user objet (optionnel) : objet représentant l'utilisateur en cours pour personnalisation du menu
     * \param $displayInline booléen (optionnel) : spécifie si le HTML doit être affiché (true) ou ajouté au corps du document (false, par défaut)
     */
    public function buildMenu(?User $user = null, $displayInline = false)
    {

        $sqlQuery = \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier()->get(SQLQuery::class);

        $userSQL = new UserSQL($sqlQuery);
        $userInfo = $userSQL->getInfo($user->getId());
        $moduleSQL = new ModuleSQL($sqlQuery);

        $modulesInfo = $moduleSQL->getModulesForUser($userInfo);

        $menuHTML = new MenuHTML();
        $html =  $menuHTML->getMenuContent($userInfo, $modulesInfo);

        if ($displayInline) {
            echo $html;
        } else {
            $this->addBody($html);
        }
    }


    /**
     * \brief Méthode de construction du pied de page du document
     * \param $displayInline booléen (optionnel) : spécifie si le HTML doit être affiché (true) ou ajouté au corps du document (false, par défaut)
     */
    public function buildFooter($displayInline = false)
    {

        $html = "        <footer class=\"bs-footer\">\n            <div class=\"container\">\n";

        if (defined("WEBMASTER")) {
            $html .= "                <a href=\"" . WEBMASTER . "\" class=\"link-white\">Webmaster</a> - \n";
        }

        if (defined("SUPPORT_URL")) {
            $html .= "                <a href=\"" . SUPPORT_URL . "\" class=\"link-white\">Support</a> - \n";
        }

        $versionning = VersionningFactory::getInstance();
        $versionningInfo = $versionning->getAllInfo();

        $html .= "                    Offre S²LOW - <a href=\"" . Helpers::getLink("/common/release_notes.php\">\n") .
            $versionningInfo['version-complete'] . "</a>\n";

        if (defined(RENDER_STARTING_TIME)) {
            $html .= " - " . round(1000 * (microtime(true) - RENDER_STARTING_TIME)) . " ms\n";
        }

        $html .= "            </div>\n        </footer>\n";

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
    public function buildPager($dataObj, $displayInline = false)
    {
        $nb_total_page = $dataObj->get("pageNbr");
        $page_number = $dataObj->get("currentPage");
        $page = array(1,2,3,$page_number  - 1 , $page_number , $page_number + 1,$nb_total_page - 2,$nb_total_page - 1,$nb_total_page );
        $page = array_unique($page);
        sort($page);
        foreach ($page as $i => $nb_page) {
            if ($nb_page > $nb_total_page || $nb_page <= 0) {
                unset($page[$i]);
            }
        }
        $last_page = 0;
        $html = "            <div id=\"display-items\">\n";

        // Nombre de résultats par page
        $html .= "<h2>Afficher par page</h2>\n";
        $html .= "<ul class=\"pagination pagination-sm\">\n";
        foreach (array(10, 20, 50, 100) as $val) {
            if ($dataObj->get("displayItems") != $val) {
                $html .= "<li><a href=\"" . Helpers::getURLWithParam(array("count" => $val)) . "\" title=\"Afficher " . $val . " éléments par page\">" . $val . "</a></li>\n";
            } else {
                $html .= "<li class=\"disabled\"><a href=\"#\">" . $val . "</a></li>\n";
            }
        }

        $html .= "</ul>\n</div>\n";

        // Liste des pages
        $html .= "<div id=\"pages\">\n";
        $html .= "<h2>Page&nbsp;:</h2>\n";
        $args = preg_replace("/&?page=[0-9]+/", "", $_SERVER["QUERY_STRING"]);
        $args = preg_replace("/^&/", "", $args);
        $args = preg_replace("/&/", "&amp;", $args);
        $sep = (mb_strlen($args) > 0) ? "&amp;" : "";

        $html .= "<ul class=\"pagination pagination-sm\">\n";
        if ($page_number > 1) {
            $args = preg_replace("/&?page=[0-9]+/", "", $_SERVER["QUERY_STRING"]);
            $args = preg_replace("/^&/", "", $args);
            $args = preg_replace("/&/", "&amp;", $args);
            $sep = (mb_strlen($args) > 0) ? "&amp;" : "";

            $html .= "<li><a href=\"" . Helpers::getURLWithParam(array("page" => ($dataObj->get("currentPage") - 1))) . "\" title=\"Afficher la page précédente\">&laquo;</a></li>\n";
        } else {
            $html .= "<li class=\"disabled\"><a href=\"#\">&laquo;</a></li>\n";
        }
        foreach ($page as $i) {
            if ($last_page + 1 != $i) {
                $html .= "<li class=\"disabled\"><a href=\"#\">...</a></li>\n";
            }
            $last_page = $i;
            if ($page_number == $i) {
                $html .= "<li class=\"active\"><a href=\"#\">" . $i . "</a></li>\n";
            } else {
                $html .= "<li><a href=\"" . Helpers::getURLWithParam(array("page" => $i)) . "\" title=\"Afficher la page " . $i . "\">" . $i . "</a></li>\n";
            }
        }
        if ($page_number < $nb_total_page) {
            $args = preg_replace("/&?page=[0-9]+/", "", $_SERVER["QUERY_STRING"]);
            $args = preg_replace("/^&/", "", $args);
            $args = preg_replace("/&/", "&amp;", $args);
            $sep = (mb_strlen($args) > 0) ? "&amp;" : "";

            $html .= "<li><a href=\"" . Helpers::getURLWithParam(array("page" => ($dataObj->get("currentPage") + 1))) . "\" title=\"Afficher la page suivante\">&raquo;</a></li>\n";
        } else {
            $html .= "<li class=\"disabled\"><a href=\"#\">&raquo;</a></li>\n";
        }
        $html .= "</ul>\n</div>\n";


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
     * @deprecated
     */
    public function getHTMLSelect($name, $data, $selectedValue, $extraAttributes = "", $onChange = null)
    {
        if ($onChange == null) {
            $html = "<select class=\"form-control\" name=\"" . $name . "\"" . $extraAttributes . ">\n";
        } else {
            $html = '<select id="' . $name . '" class="form-control" name="' . $name . "\"" . $extraAttributes . ' onchange="' . $onChange . '">\n';
        }
        $html .= " <option value=\"\">Choisissez</option>\n";

        foreach ($data as $key => $val) {
            $html .= " <option value=\"" . $key . "\"";

            $html .= (strcmp($key, $selectedValue ?? '') == 0) ? " selected=\"selected\"" : "";

            $html .= ">" . get_hecho($val) . "</option>\n";
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
    public function getHTMLCheckbox($name, $value, $extraAttributes = "")
    {
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
    public function getHTMLArrayline($name, $value)
    {
        $html = " <tr>\n";
        $html .= "  <th class=\"td-register th-row\" scope=\"row\">" . $name . "&nbsp;:</th>\n";
        $html .= "  <td class=\"td-input\">" . $value . "</td>\n";
        $html .= " </tr>\n";

        return $html;
    }


    /**
     * \brief Méthode d'inclusion du message d'erreur stocké en session
     */
    public function includeErrors()
    {
        if ($this->errorDisabled) {
            return;
        }

        ob_start();
        if (isset($_SESSION["error"])) {
            $this->afficheErrors();
        }
        $html = ob_get_contents();
        ob_end_clean();

        //$this->addBody($html);
        // Gros hack moisi à cause d'IE qui bug à l'affichage
        // il faut "injecter" la zone d'erreur à l'intérieur de la zone "content"
        //FIXME (EP), ce n'est pas un "bug" d'IE, la CSS ne défini la errorbox qu'a l'interieur du content
        //FIXME c'est cette classe qui n'est pas très bien concu ...
        $this->body = str_replace("role=\"main\">", "role=\"main\">\n" . $html, $this->body);
    }

    public function afficheErrors()
    {
        if (! isset($_SESSION["error"]) || ! $_SESSION["error"]) {
            return;
        }
        ?>
        <div class="alert alert-warning">
            <strong><?php hecho($_SESSION["error"]); ?></strong>
        </div>

        <?php
        unset($_SESSION["error"]);
    }


    public function displayTemplate($layout, $templateFile)
    {
        $this->includeErrors();
        require_once(HTML_TEMPLATE_PATH . "/" . "new.generic.tpl.php");
    }


    public function addCSS($css)
    {
        $this->addHeader("<link rel='stylesheet' type='text/css' href='$css' />");
    }

    public function addJavascript($javascript)
    {
        $this->addHeader("<script src='$javascript' type='text/javascript'></script>");
    }


    /**
     * \brief Méthode générant l'affichage du document
     */

    public function display()
    {
        $this->includeErrors();

        if ($this->template) {
            //Note EP 09/09/2015 : avant il y avait require_once, ce qui pour un template est ... spécial.
            //Du coup, ca passe pas les tests unitaire... je mets include, mais je sais pas ce que ca va donner...
            include(HTML_TEMPLATE_PATH . "/" . $this->template);
        } else {
            echo "<?xml version=\"1.0\" encoding=\"iso-8859-15\"?>\n";
            echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n";
            echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"fr\">\n";
            echo "    <head>\n";
            echo "        <title>" . $this->title . "</title>\n";
            echo "        <meta name='unicite' content='4'>\n";
            echo $this->header . "\n";
            echo "    </head>\n";
            echo "    <body>\n";
            echo "        <div class=\"container\">\n";
            echo "            <div class=\"row\">\n";
            echo $this->body . "\n";
            echo "            </div>\n";
            echo "        </div>\n";
            echo "    </body>\n";
        }
    }
}
