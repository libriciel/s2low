<?php

// Instanciation du module courant
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\HTMLLayoutFactory;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

/** @var HTMLLayoutFactory $htmlLayoutFactory */
$htmlLayoutFactory = \S2lowLegacy\Class\LegacyObjectsManager::getObject(HTMLLayoutFactory::class);

$module = new Module();
if (!$module->initByName("actes")) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
    exit();
}

if ($me->isGroupAdminOrSuper() || !$module->isActive() || !$me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$myAuthority = new Authority($me->get("authority_id"));

$doc = $htmlLayoutFactory->createLayout();

$doc->setTitle("Tedetis : Traitement par lots module actes");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu();
$doc->closeSideBar();
$doc->openContent();


$css = '';
$js = '';

$js .= "
    <script type=\"text/javascript\" src=\"" . Helpers::getLink("/jsmodules/jquery.js") . "\"></script>
    <script type=\"text/javascript\" src=\"" . Helpers::getLink("/jsmodules/jqueryfileupload.js") . "\"></script>\n";

$doc->addHeader($css . $js);

$html = "<h1>ACTES - Traitement par lots</h1>\n";
$html .= "<h2>Cr&eacute;ation d'un lot</h1>\n";

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../../../templates');
$twig = new \Twig\Environment($loader);

$html .= $twig->render('batch_creator.html.twig', [
    "userid" => $me->getId(),
    "website_ssl" => WEBSITE_SSL
]);

$html .= "<script>
$(document).ready(function(){
    $('#jfu_batch_id').val('');     // RàZ des traces d'un éventuel batch
    $('#jfu_batch_request').val(0); // et d'une éventuelle requête
    
    $('#fileupload').css('display', 'none');
    
    $('#batchformsubmit').click(function() {
            if ($('#jfu_intitule').val() == '') {
                alert('Le champ \"Intitulé du lot\" est vide.');
                return false;
            }
            if ($('#jfu_num_prefix').val() == '') {
                alert('Le champ \"Préfixe des numéros internes\" est vide.');
                return false;
            }
            $('#batchproperties input').attr('readonly', 'readonly');
            $('#batchformsubmit').prop('disabled',true);
            $('#batchformsubmit').hide();
            $('#fileupload').css('display', 'contents');
    });
});
</script>";
//$html .= "</div>";

$html .= " <div class=\"testTTTT\" >\n";

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../../../templates');
$twig = new \Twig\Environment($loader);

$html .= $twig->render('jfu.html.twig', [
    "userid" => $me->getId(),
    "batch_id" => '',
    "actes_max_batch_upload_site" => ACTES_MAX_BATCH_UPLOAD_SIZE,
    "website_ssl" => WEBSITE_SSL
]);

$html .=    "<script type=\"text/javascript\">\n";
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
$html .= "//verifMultiUpload();\n";

//ici on masque par defaut le bouton d envoi. Il sera afficher si aucun fichier invalide n est present dans la liste
$html .= "/*$('.start').css('display', 'none');*/
    </script>\n
  ";


$html .= "</div>";

$doc->addBody($html);

$doc->closeContent();

$doc->closeContainer();

$doc->buildFooter();

$doc->display();
