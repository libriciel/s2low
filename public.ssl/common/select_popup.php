<?php

use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\User;

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Éhec de l'authentification";
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
    exit();
}

$type = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromGet("type", true);

$myAuthority = new Authority($me->get("authority_id"));

$doc = new HTMLLayout("xhtml_simple.tpl.php");
$doc->addHeader("<script type=\"text/javascript\" src=\"" . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/jsmodules/jquery.js") . "\"></script>");

$doc->setTitle("Tedetis : sélection attribut");

$html = "<div id=\"attribute_list\">\n";

switch ($type) {
    case 'authority_type':
        if (! $me->isAdmin()) {
            $_SESSION["error"] = "Accès refusé";
            header("Location: " . WEBSITE_SSL);
            exit();
        }

        $types = Authority::getAuthorityTypes();

        $html .= "<dl>\n";

        foreach ($types as $type) {
            if ($type["type"] == 'parent') {
                $html .= "<dt>" . $type["code"] . "&nbsp;-&nbsp;" . $type["description"] . "</dt>\n";
            } elseif ($type["type"] == 'child') {
                $html .= "<dd><a href=\"#tedetis\" onclick=\"javascript:return_choice('" . $type["code"] . "', '" . addslashes($type["description"]) . "');\">" . $type["code"] . "&nbsp;-&nbsp;" . $type["description"] . "</a></dd>\n";
            }
        }

        $html .= "</dl>\n";

        $js = "<script type=\"text/javascript\">\n";
        $js .= "function return_choice(value, text) {\n";
        $js .= "  window.opener.document.getElementById('authority_type').value = value;\n";
        $js .= "  window.opener.document.getElementById('authority_type_text').innerHTML = value + '&nbsp;-&nbsp;' + text;\n";
        $js .= "  window.close();\n";
        $js .= "}\n";
        $js .= "</script>\n";
        break;

    case 'department':
        if (! $me->isAdmin()) {
            $_SESSION["error"] = "Accès refusé";
            header("Location: " . WEBSITE_SSL);
            exit();
        }

        $depts = Authority::getDepartmentsList();

        $html .= "<dl>\n";

        foreach ($depts as $dept) {
            $html .= "<dt>" . $dept["code"] . "&nbsp;-&nbsp;" . $dept["name"] . "</dt>\n";

            $districts = Authority::getDistrictsForDepartment($dept["code"]);

            foreach ($districts as $code => $district) {
                $html .= "<dd><a href=\"#tedetis\" onclick=\"javascript:return_choice('" . addslashes($dept["code"]) . "', '" . addslashes($code) . "', '" . addslashes($dept["name"]) . "&nbsp;/&nbsp;" . addslashes($district) . "');\">" . $code . "&nbsp;-&nbsp;" . $district . "</a></dd>\n";
            }
        }

        $html .= "</dl>\n";

        $js = "<script type=\"text/javascript\">\n";
        $js .= "function return_choice(dept, distr, text) {\n";
        $js .= "  window.opener.document.getElementById('department').value = dept;\n";
        $js .= "  window.opener.document.getElementById('district').value = distr;\n";
        $js .= "  window.opener.document.getElementById('department_text').innerHTML = text;\n";
        $js .= "  window.close();\n";
        $js .= "}\n";
        $js .= "</script>\n";
        break;

    case 'classification':
        $classifications = ActesClassification::getClassificationList($myAuthority->getId());

        $done = array();

        if (count($classifications) > 0) {
            foreach ($classifications as $key => $classification) {
                  $codes = array();
                  $items = array();
                  $item = $classification;
                  $level = 1;

                while ($item) {
                    if (empty($done[$item["id"]])) {
                        // Item non encore traité
                        // On l'ajoute sur la pile
                        array_push($codes, $item["code"]);
                        // On l'affiche
                        $html .= "<a class=\"tree_level_" . $level . "\" href=\"#tedetis\" onclick=\"javascript:return_choice('" . implode(".", $codes) . "&nbsp;-&nbsp;" . str_replace('"', "&quot;", str_replace("'", "\\'", $item["description"])) . "', " . implode(",", $codes) . ");\">" . implode(".", $codes) . "&nbsp;-&nbsp;" . $item["description"] . "</a><br />\n";
                        // On le marque comme traité
                        $done[$item["id"]] = true;
                    }

              // Traitement des items enfants de l'item courant
                    if (isset($item["children_id"]) && count($item["children_id"]) > 0) {
                         $par_item = $item;
                         $level++;
                         // On passe au prochain enfant de l'item courant en le supprimant du tableau des enfants
                         $item = $classifications[array_shift($par_item["children_id"])];
                         // On ajoute l'item parent sur la pile des items pour continuer le traitement des enfants lors de la remontée
                         array_push($items, $par_item);
                    } else {
                     // Plus d'enfant => on remonte la pile des items
                          $item = array_pop($items);
                     // On remonte le code courant également
                          array_pop($codes);
                          $level--;
                    }
                }
            }

            $js = "<script type=\"text/javascript\">\n";
            $js .= "function return_choice(text) {\n";
            $js .= "  args = return_choice.arguments;\n\n";
            $js .= "  for (i = 1; i <= 5; i++) {\n";
            $js .= "    zeVar = 'classif' + i;\n";
            $js .= "    elt = window.opener.document.getElementById(zeVar);\n";
            $js .= "    if (args[i]) {\n";
            $js .= "      elt.value = args[i];\n";
            $js .= "    } else {\n";
            $js .= "      elt.value = '';\n";
            $js .= "    }\n";
            $js .= "  }\n";
            $js .= "  window.opener.document.getElementById('classification_text').innerHTML = text;\n";
            $js .= "   window.opener.$(\"#classification_text\").trigger('change'); ";
            $js .= "  window.close();\n";
            $js .= "}\n";
            $js .= "</script>\n";
        } else {
            $html .= "Pas de classification matières/sous-matières associée à votre collectivité.<br />\n";
            $html .= "Utilisez le bouton «&nbsp;Mettre à jour la classification&nbsp;» de l'interface de création de transaction pour effectuer une demande de récupération de la classification.<br />";
            $js = "";
        }

        break;

    default:
        echo 'Appel incorrect';
        exit();
}


$html .= "</div>\n";
$html .= "<a href=\"#tedetis\" onclick=\"javascript:window.close();\">Fermer la fenêtre</a>\n";

$doc->addBody($html);

$doc->addHeader($js);

$doc->display();
