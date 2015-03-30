<?php

/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006
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
 * \file public.ssl/modules/actes/actes_batch_add.php
 * \brief Page de création d'une transaction par lots
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 08.02.2007
 *
 *
 * Cette page permet la création d'un lot
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */
// Configuration
require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
	$_SESSION["error"] = "Erreur d'initialisation du module";
	header("Location: " . WEBSITE_SSL);
	exit();
}

$me = new User();

if (!$me->authenticate()) {
	$_SESSION["error"] = "Échec de l'authentification";
	header("Location: " . WEBSITE);
	exit();
}

if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
	$_SESSION["error"] = "Accès refusé";
	header("Location: " . WEBSITE_SSL);
	exit();
}

$myAuthority = new Authority($me->get("authority_id"));

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : Traitement par lots module actes");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

/*
 * Modifications : Stéphane Sampaio
 */
//la derniere ligne permet d ajouter la feuille css du plugin d upload jquery
/*$css = "
    <link rel=\"stylesheet\" href=\"/javascript/jfu/css/bootstrap.min.css\">\n
    <link rel=\"stylesheet\" href=\"/javascript/jfu/css/bootstrap-responsive.min.css\">\n
    <!--[if lt IE 7]><link rel=\"stylesheet\" href=\"/javascript/jfu/css/bootstrap-ie6.min.css\"><![endif]-->\n
    <link rel=\"stylesheet\" href=\"/javascript/jfu/css/bootstrap-image-gallery.min.css\">\n
    <link rel=\"stylesheet\" href=\"/javascript/jfu/css/jquery.fileupload-ui.css\">\n
";*/




$css = '';
$js = '';

/*
 * Modifications : Stéphane Sampaio
 */
//cette fonction javascript n est plus utile

/* $js = "<script type=\"text/javascript\">\n";
  $js .= "  function redirect_to_batch(batch_id) {\n";
  $js .= "    alert('Redirect to batch');\n";
  $js .= "    document.location = '" . WEBSITE_SSL . "/modules/actes/actes_batch_show.php?id=' + batch_id;\n";
  $js .= "  }\n";
  $js .= "</script>\n"; */

/*
 * Modifications : Stéphane Sampaio
 */
//ces lignes permettent de charger le plugin d upload jquery

$js .= "
<script type=\"text/javascript\" src=\"/javascript/jfu/js/jquery.min.js\"></script>\n
    <script type=\"text/javascript\" src=\"/javascript/jfu/js/vendor/jquery.ui.widget.js\"></script>\n
    <script type=\"text/javascript\" src=\"/javascript/jfu/js/tmpl.min.js\"></script>\n
    <script type=\"text/javascript\" src=\"/javascript/jfu/js/load-image.min.js\"></script>\n
    <script type=\"text/javascript\" src=\"/javascript/jfu/js/canvas-to-blob.min.js\"></script>\n
    <script type=\"text/javascript\" src=\"/javascript/jfu/js/bootstrap.min.js\"></script>\n
    <script type=\"text/javascript\" src=\"/javascript/jfu/js/bootstrap-image-gallery.min.js\"></script>\n
    <script type=\"text/javascript\" src=\"/javascript/jfu/js/jquery.iframe-transport.js\"></script>\n
    <script type=\"text/javascript\" src=\"/javascript/jfu/js/jquery.fileupload.js\"></script>\n
    <script type=\"text/javascript\" src=\"/javascript/jfu/js/jquery.fileupload-fp.js\"></script>\n
    <script type=\"text/javascript\" src=\"/javascript/jfu/js/jquery.fileupload-ui.js\"></script>\n
    <script type=\"text/javascript\" src=\"/javascript/jfu/js/locale.js\"></script>\n
    <script type=\"text/javascript\" src=\"/javascript/jfu/js/main.js.php\"></script>\n
    <!--[if gte IE 8]><script type=\"text-javascript\" src=\"/javascript/jfu/js/cors/jquery.xdr-transport.js\"></script><![endif]-->\n

";

$doc->addHeader($css . $js);

/*
 * Modifications : Stéphane Sampaio
 */
//Construction du formulaire du plugin d upload jquery

$html = "<h1>ACTES - Traitement par lots</h1>\n";
$html .= "<h2>Cr&eacute;ation d'un lot</h1>\n";

$html .= " <div class='noMultipleSelect'>" . ACTES_BATCH_UPLOAD_PLUGIN_FALLBACK_MESSAGE . "</div>\n";

$html .= " <div class=\"jfu_controls\">\n";

/*
 * Modifications : Stéphane Sampaio
 */

$html .= "<form class=\"form form-horizontal\" id=\"fileupload\" action=\"actes_batch_create.php\" method=\"POST\" enctype=\"multipart/form-data\">\n";

//ici on reprend les champs necessaires pour l identification du lot
$html .= "<input id=\"jfu_user_id\" type=\"hidden\" name=\"user_id\" value=\"" . $me->getId() . "\"/>\n
	<div class=\"form-group\">\n
            <label for=\"jfu_intitule\" class=\"col-md-4 label-form\">Intitul&eacute; du lot : </label>\n
            <div class=\"col-md-4\">
		<input id=\"jfu_intitule\" class=\"form-control\" type=\"text\" name=\"intitule\" />\n
            </div>\n
        </div>\n
	<div class=\"form-group\">\n        
            <label for=\"jfu_num_prefix\" class=\"col-md-4 label-form\">Pr&eacute;fixe des num&eacute;ros internes : </label>\n
            <div class=\"col-md-4\">
                <input id=\"jfu_num_prefix\" class=\"form-control\" type=\"text\" name=\"prefixe\" maxlength=\"12\" />\n
            </div>\n
        </div>\n
";

//ceci est le formulaire de base du plugin
$html .= "<!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->\n
        <div class=\"form-group\">\n
          <div class=\"fileupload-buttonbar\">\n
            <!-- The fileinput-button span is used to style the file input field as button -->\n
            <span class=\"btn btn-success fileinput-button\">\n
              <i class=\"icon-plus icon-white\"></i>\n
              <span>Ajouter des fichiers...</span>\n
              <input type=\"file\" name=\"files[]\" id=\"filelist\" multiple>\n
            </span>\n
            <button type=\"submit\" class=\"btn btn-primary start\">\n
              <i class=\"icon-upload icon-white\"></i>\n
              <span>Creer le lot</span>\n
            </button>\n";

//on commente les boutons de remise a zero et de suppression ainsi que la case a cocher dans le haut du formulaire (ceux qui permettent les actions pour toute la liste de fichiers)
$html .= "<!--   <button type=\"reset\" class=\"btn btn-warning cancel\">\n
              <i class=\"icon-ban-circle icon-white\"></i>\n
              <span>Annuler</span>\n
            </button>\n
            <button type=\"button\" class=\"btn btn-danger delete\">\n
              <i class=\"icon-trash icon-white\"></i>\n
              <span>Supprimer</span>\n
            </button>\n
            <input type=\"checkbox\" class=\"toggle\">\n -->";

//cette partie correspond a la barre de progression visible quand on envoi les fichiers
$html .= "</div>\n
          <!-- The global progress information -->\n
          <div class=\"span5 fileupload-progress fade\">\n
            <!-- The global progress bar -->\n
            <div class=\"progress progress-success progress-striped active\">\n
              <div class=\"bar\" style=\"width:0%;\"></div>\n
            </div>\n
            <!-- The extended global progress information -->\n
            <div class=\"progress-extended\">&nbsp;</div>\n
          </div>\n
        </div>\n
        <!-- The loading indicator is shown during file processing -->\n
        <div class=\"fileupload-loading\"></div>\n
        <!-- The table listing the files available for upload/download -->\n
        <table class=\"table table-striped\"><tbody class=\"files\" data-toggle=\"modal-gallery\" data-target=\"#modal-gallery\"></tbody></table>\n
      </form>\n
    </div>\n";

//ici commence la liste des fichiers selectionnes
$html .= "<div class=\"jfu_fileList\">\n
      <div id=\"modal-gallery\" class=\"modal modal-gallery hide fade\" data-filter=\":odd\">\n
        <div class=\"modal-header\">\n
          <a class=\"close\" data-dismiss=\"modal\">&times;</a>\n
          <h3 class=\"modal-title\"></h3>\n
        </div>\n
        <div class=\"modal-body\"><div class=\"modal-image\"></div></div>\n
        <div class=\"modal-footer\">\n
          <a class=\"btn modal-download\" target=\"_blank\">\n
            <i class=\"icon-download\"></i>\n
            <span>Download</span>\n
          </a>\n
          <a class=\"btn btn-success modal-play modal-slideshow\" data-slideshow=\"5000\">\n
            <i class=\"icon-play icon-white\"></i>\n
            <span>Slideshow</span>\n
          </a>\n
          <a class=\"btn btn-primary modal-prev\">\n
            <i class=\"icon-arrow-left icon-white\"></i>\n
            <span>Previous</span>\n
          </a>\n
          <a class=\"btn btn-primary modal-next\">\n
            <span>Next</span>\n
            <i class=\"icon-arrow-right icon-white\"></i>\n
          </a>\n
        </div>\n
      </div>\n";

//ceci est la liste des fichiers a envoyer (donc ceux que l on selectionne a partir du disque dur du poste client)
//une gallerie propose des previews des images, dans le cas present ce n est pas utile, mais on ne peut supprimer cette galerie sans faire planter le plugin
//une recherche approfondie de la documentation donnera certainement des pistes.
$html .= "<!-- The template to display files available for upload -->\n
      <script id=\"template-upload\" type=\"text/x-tmpl\">\n
        {% for (var i=0, file; file=o.files[i]; i++) { %}\n
        <tr class=\"template-upload fade\">\n
          <td class=\"preview\"><span class=\"fade\"></span></td>\n
          <td class=\"name\"><span>{%=file.name%}</span></td>\n
          <td class=\"size\"><span>{%=o.formatFileSize(file.size)%}</span></td>\n
          {% if (file.error) { %}\n
          <td class=\"error\" colspan=\"2\"><span class=\"label label-important\">{%=locale.fileupload.error%}</span> {%=locale.fileupload.errors[file.error] || file.error%}</td>\n
          {% } else if (o.files.valid && !i) { %}\n
          <td>\n
          <div><div></div></div>
<!--            <div class=\"progress progress-success progress-striped active\"><div class=\"bar\" style=\"width:0%;\"></div></div>\n -->
          </td>\n

       <td class=\"start\">{% if (!o.options.autoUpload) { %}\n
        <button style=\"display:none;\" class=\"btn btn-primary\">\n
              <i class=\"icon-upload icon-white\"></i>\n
              <span>{%=locale.fileupload.start%}</span>\n
            </button>\n
            {% } %}</td>\n



          {% } else { %}\n
          <td colspan=\"2\"></td>\n
          {% } %}\n";

//on desactive le bouton d annulation de chaque fichier pret a l envoi
$html .= "<!-- <td class=\"cancel\">{% if (!i) { %}\n
            <button class=\"btn btn-warning\">\n
              <i class=\"icon-ban-circle icon-white\"></i>\n
              <span>{%=locale.fileupload.cancel%}</span>\n
            </button>\n -->


            {% } %}</td>\n


            <td class=\"manualdelete\">{% if (!i) { %}\n
            <span class=\"btn btn-warning\" onclick=manualDeleteLine(this);>\n
              <i class=\"icon-ban-circle icon-white\"></i>\n
              <span>Supprimer</span>\n
            </span>\n
 {% } %}</td>\n


        </tr>\n
        {% } %}\n
      </script>\n";
//ici commence la liste des fichiers a telecharger. Cette partie n est pas utilisee dans le cas present, car une fois les fichiers envoyes, on
//change de page. La suppression de cette partie fait planter le plugin, c est pourquoi elle est toujours presente.
//a nouveau une recherche appronfondie dans la documentation du plugin donnera certainement des pistes pour se passer de cette partie.
$html .= "<!-- The template to display files available for download -->\n
      <script id=\"template-download\" type=\"text/x-tmpl\">\n
      {% for (var i=0, file; file=o.files[i]; i++) { %}\n

        <tr class=\"template-download fade\">\n
          {% if (file.error) { %}\n
          <td></td>\n
          <td class=\"name\"><span>{%=file.name%}</span></td>\n
          <td class=\"size\"><span>{%=o.formatFileSize(file.size)%}</span></td>\n
          <td class=\"error\" colspan=\"2\"><span class=\"label label-important\">{%=locale.fileupload.error%}</span> {%=locale.fileupload.errors[file.error] || file.error%}</td>\n
          {% } else { %}\n
          <td class=\"preview\">{% if (file.thumbnail_url) { %}\n
            <a href=\"{%=file.url%}\" title=\"{%=file.name%}\" rel=\"gallery\" download=\"{%=file.name%}\"><img src=\"{%=file.thumbnail_url%}\"></a>\n
            {% } %}</td>\n
          <td class=\"name\">\n
            <a href=\"{%=file.url%}\" title=\"{%=file.name%}\" rel=\"{%=file.thumbnail_url&&'gallery'%}\" download=\"{%=file.name%}\">{%=file.name%}</a>\n
          </td>\n
          <td class=\"size\"><span>{%=o.formatFileSize(file.size)%}</span></td>\n
          <td colspan=\"2\"></td>\n
          {% } %}\n
          <td class=\"delete\">\n
          <!--  <button class=\"btn btn-danger\" data-type=\"{%=file.delete_type%}\" data-url=\"{%=file.delete_url%}\">\n
              <i class=\"icon-trash icon-white\"></i>\n
              <span>{%=locale.fileupload.destroy%}</span>\n
            </button>\n
            <input type=\"checkbox\" name=\"delete\" value=\"1\">\n -->
          </td>\n
        </tr>\n
        {% } %}\n
      </script>\n
    </div>\n
    <script type=\"text/javascript\">\n";

//cette partie permet les verficiations de validite des champs du formulaire. Elle ne fait pas partie du plugin d upload
$html .= "var spanAlert = $('<span></span>').html('Seuls les caract&egrave;res alphab&eacute;tiques, num&eacute;riques et le caract&egrave;re soulign&eacute; \"_\" sont accept&eacute;s.').css({
      'color': 'red',
      'margin-left': '10px',
      'display': 'none'
    }).attr('id', 'spanAlert');

    $('#jfu_num_prefix').after(spanAlert).keyup(function(){\n
      $(this).val($(this).val().toUpperCase());\n";

//l expression reguliere suivante permet de valider le format des information saisies dans le champ 'prefixe'. Le test de validite est effectuer lorsque l'on relache une touche du clavier
$html .= "var test = $(this).val().match(/[^A-Z0-9_]*/g);
      var doAlert = false;
      for (i in test){
        if (test[i] != ''){
          doAlert = true;
        }
      }
      if (doAlert){
        $('#spanAlert').css('display', 'inline');
      } else {
        $('#spanAlert').css('display', 'none');
      }

    });\n";


//on lance le test javascript pour vérifire si le navigateur client peut faire de la sélection multiple
$html .="verifMultiUpload();\n";

//ici on masque par defaut le bouton d envoi. Il sera afficher si aucun fichier invalide n est present dans la liste
$html .="$('.start').css('display', 'none');
    </script>\n
  ";


$html .= "</div>";

/*
 * Modifications : Stéphane Sampaio
 */
//Les lignes suivantes sont celles etant presentes avant la mise en place du plugin jquery
//$html = "<div id=\"content\">\n";
//$html .= "<h1>ACTES - Cr&eacute;ation d'un lot</h1>\n";
//
//$html .= " <object classid=\"java:selection.AppletMultipleSelection\" type=\"application/x-java-applet;version=1.5\" archive=\" applet/AppletMultipleSelection.jar, applet/commons-httpclient-3.0.1.jar, applet/bcmail-jdk15-133.jar, applet/commons-logging-api.jar, applet/commons-codec-1.3.jar, applet/bcprov-jdk15-133.jar\" height=\"400\" width=\"750\">\n";
//$html .= " <!-- Konqueror browser needs the following param -->\n";
//$html .= ' <param name="charset" value="Cp1252">';
//$html .= " <param name=\"archive\" value=\"applet/AppletMultipleSelection.jar, applet/commons-httpclient-3.0.1.jar, applet/bcmail-jdk15-133.jar, applet/commons-logging-api.jar, applet/commons-codec-1.3.jar, applet/bcprov-jdk15-133.jar\" />\n";
//$html .= " <param name=\"max_size\" value=\"" . ACTES_MAX_BATCH_UPLOAD_SIZE . "\" />\n";
//$html .= " <param name=\"url\" value=\"" . WEBSITE_SSL . "/modules/actes/actes_batch_create.php\" />\n";
//$html .= " <param name=\"url_redirect\" value=\"" . WEBSITE_SSL . "/modules/actes/actes_batch_show.php?id=\" />\n";
//$html .= " <param name=\"mayscript\" value=\"true\"/>\n";
//$html .= " <!--<![endif]-->\n";
//$html .= " <!-- MSIE (Microsoft Internet Explorer) will use inner object -->\n";
//$html .= " <object classid=\"clsid:8AD9C840-044E-11D1-B3E9-00805F499D93\" codebase=\"http://java.sun.com/update/1.5.0/jinstall-1_5_0-windows-i586.cab\" height=\"400\" width=\"750\">\n";
//$html .= ' <param name="charset" value="Cp1252">';
//$html .= " <param name=\"code\" value=\"selection.AppletMultipleSelection\" />\n";
//$html .= " <param name=\"archive\" value=\"applet/AppletMultipleSelection.jar, applet/commons-httpclient-3.0.1.jar, applet/bcmail-jdk15-133.jar, applet/commons-logging-api.jar, applet/commons-codec-1.3.jar, applet/bcprov-jdk15-133.jar\" />\n";
//$html .= " <param name=\"max_size\" value=\"" . ACTES_MAX_BATCH_UPLOAD_SIZE . "\" />\n";
//$html .= " <param name=\"url\" value=\"" . WEBSITE_SSL . "/modules/actes/actes_batch_create.php\" />\n";
//$html .= " <param name=\"url_redirect\" value=\"" . WEBSITE_SSL . "/modules/actes/actes_batch_show.php?id=\" />\n";
//$html .= " <param name=\"mayscript\" value=\"true\"/>\n";
//$html .= " <strong>\n";
//$html .= " Votre navigateur ne comporte pas le support de Java.<br />\n";
//$html .= " <a href=\"http://java.sun.com/javase/downloads/index.jsp\">Obtenir le dernier plugin Java.</a>\n";
//$html .= " </strong>\n";
//$html .= " </object>\n";
//$html .= " <!--[if !IE]>-->\n";
//$html .= " </object>\n";
//$html .= " <!--<![endif]-->\n";
//$html .= " <p><a href=\"http://java.sun.com/javase/downloads/index.jsp\">Plugin Java en version 1.6</a> minimum requis pour le fonctionnement correct de cette page.</p>\n";
//$html .= "</div>\n";

$doc->addBody($html);

$doc->closeContent();

$doc->closeContainer();

$doc->buildFooter();

$doc->display();
?>