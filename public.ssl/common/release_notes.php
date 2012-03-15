<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeurs : Jérôme Schell, Aout 2006
 *                 Eric Pommateau, Tan Hao,  
 *                 Jean-Francois Mourgues
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à   la
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
 * associés au chargement,  à   l'utilisation,  à   la modification et/ou au
 * développement et à   la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à   
 * manipuler et qui le réserve donc à   des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à   charger  et  tester  l'adéquation  du
 * logiciel à   leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * à  l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder à cet en-tte signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/
?>
<?php
/**
 * \file release_notes.php
 * \brief Page d'affichage des release notes de l'application
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 25.08.2006
 * 
 *
 * Cette page affiche les notes de publication des différentes versions
 * de l'application TéDéTis.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

$doc = new HTMLLayout();

$doc->setTitle("Logiciel TéDéTIS : Notes de publication");

$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<p> On trouvera  après la note de dernière version la liste des limitations connues pour cette version</p>";
$html .= "<h1>Logiciel TéDéTIS - notes de publication</h1>\n";


$html .= "";

// $html .= "<h2>V1.0.8.3 du </h2>";
// $html .= "<ul>";
// $html .= "<li>";
// $html .= "</ul>";
$html .= "<h2>V1.2.1 du 09.03.2012</h2>";
$html .= "<ul>Module ACTES et MAIL : correction bug : modification des entêtes des mails envoyés pour ne plus avoir de BAD HEADER";
$html .= "<li>Module HELIOS : correction bug : en cas d erreur à lors de la transmis d un flux, un message indiquant le problème est fourni";
$html .= "<li>Module MAIL : correction bug : modification du code HTML pour ne plus être détecté à tort comme du SPAM";
$html .= "<li>Administration : correction bug : les siren sont vérifiés lorsqu ils sont ajoutés via le formulaire";
$html .= "<li>Administration : correction bug : problème d authentification avec des certificats dont les noms des AC comportent des accents";
$html .= "<li>Module ACTES : prise en compte des retours du MIOCT dont la partie numéro dépasse 4 caractères";
$html .= "<li>Module ACTES : ajout du statut classification mise à jour";
$html .= "<li>Module ACTES : suppression des dossiers et fichiers temporaires";
$html .= "<li>Module ACTES : les administrateurs de collectivités peuvent visualiser le détail des transactions";
$html .= "<li>Module HELIOS : prise en compte des nouveaux PES ACK délivrés par le DGFiP";
$html .= "<li>Module HELIOS : nouvelle méthode de récupération des flux mis à disposition par la DGFiP";
$html .= "<li>Module HELIOS : prise en compte des fichiers vided présents sur les serveurs de la DGFiP";
$html .= "<li>Module HELIOS : l administrateur de groupe peut lister les transactions par collectivité";
$html .= "<li>Administration : modification des intitulés des champs SAE dans les paramètres des collectivités";
$html .= "</ul>";

$html .= "<h2>V1.2 du 16.11.2011</h2>";
$html .= "<ul>";
$html .= "<li>Module ACTES : Correction bug : Informations complémentaires sur les réponses aux flux 3 et4";
$html .= "<li>Module ACTES : Correction bug : Notifications à ne pas envoyer aux utilisateurs désactivés";
$html .= "<li>Module ACTES : Correction bug : Notification automatiques bloquées";
$html .= "<li>Module ACTES : Correction bug : Messages d erreurs eronnés lors de la création des actes";
$html .= "<li>Module ACTES : Correction bug : Mails de notifications envoyés en doubles";
$html .= "<li>Module ACTES : Correction bug : Les caractères Microsft Word sont acceptés";
$html .= "<li>Module ACTES : Correction bug : La limite du nombre d annexes dans les transactions est augmentée et peut être modifiée simplement";
$html .= "<li>Module ACTES : Correction bug : L agent télétransmetteur est desormais notifié automatiquement";
$html .= "<li>Module ACTES : Correction bug : Tous les caractères sont acceptés dans l objet";
$html .= "<li>Module ACTES : Correction bug : La limite du nombre de fichiers dans le traitement par lot est augmentée et peut être modifiée simplement";
$html .= "<li>Module ACTES : Correction bug : Dans le tampon l orthographe a été corrigée";
$html .= "<li>Module MAIL : Correction bug : Les destinataires sont affichés par ordre alphabétiques pour comparer plusieurs messages sur les mêmes listes";
$html .= "<li>Module MAIL : Correction bug : Saisie des adresses avec la souris sur Microsoft Internet Explorer";
$html .= "<li>Module MAIL : Correction bug : La macro TEXT est utilisée";
$html .= "<li>Administration : Correction bug : Login unique pour l ensemble de la plateforme";
$html .= "<li>Administration : Correction bug : Les certificats avec accents ne pouvaient pas être utilisés avec un login";
$html .= "<li>Module ACTES : Intégration des ACTES BUDGETAIRES";
$html .= "<li>Module ACTES : Connexion avec le SAE AS@LAE";
$html .= "<li>Module ACTES : Création d une API pour récupérer l ACTES avec le tampon";
$html .= "<li>Module ACTES : Amélioration des requêtes SQL";
$html .= "<li>Module ACTES : Le nom de la collectivité est indiqué dans les mails de notifications";
$html .= "<li>Module ACTES : Il est possible d effectuer des recherches sur l objet";
$html .= "<li>Module HELIOS: La collectivité émétrice est indiquée dans le détail de la transaction et dans la liste des transactions";
$html .= "<li>Module HELIOS: Le propriétaire de la transaction est indiqué dans le détail de celle-ci et dans la liste des transactions";
$html .= "<li>Module MAIL : Il est possible de modifier un contact";
$html .= "<li>Module MAIL : Le texte du mail reçu a été modifié pour ne plus être équivoque";
$html .= "<li>Module MAIL : Via les API il est possible d envoyer un mail sécurisé sans le/les destinataires soient présents dans le carnet d adresses";
$html .= "<li>Administration: La date d expiration du certificat de l utilisateur est indiquée";
$html .= "<li>Administration: Les SIREN sont affichés par ordre croissant";
$html .= "<li>Administration: Il possible d ajouter directement un SIREN dans un groupe";
$html .= "<li>Administration: Les utilisateurs sont triés par ordre alphabétiques";
$html .= "</ul>";

$html .= "<h2>V1.1 du 16.11.2010</h2>";
$html .= "<ul>";
$html .= "<li>Module ACTES : Intégration des flux ACTES 1.4";
$html .= "<li>Module ACTES : Traitement par lot : les actes peuvent se situer dans des dossiers différents";
$html .= "<li>Module ACTES : Les actes sont disponibles avec tampon indiquant la date d'envoie à la prefecture et de réception par celle-ci";
$html .= "<li>Module ACTES : Le mail de notification d'acqusé de réception inclue le borderau d'acquitement";
$html .= "<li>Module ACTES : La classification est mise à jour automatiquement";
$html .= "<li>Module ACTES : Intégration des groupes/services";
$html .= "<li>Module ACTES : Le bouton valider apparait au bout de 2 mois après l'acquitement";
$html .= "<li>Module ACTES : Les recherches ne sont plus sensibles à la case";
$html .= "<li>Module ACTES : Les collectivités et groupes sont listés par ordre alphabétique";
$html .= "<li>Module ACTES : Un même certificat peut être utilisé par plusieurs utilisateur via un login/mot de passe";
$html .= "<li>Module ACTES : Le descriptif du certificat apparait dans la fiche de l'utilisateur";
$html .= "<li>Module ACTES : Les dates de décisions ne peuvent plus être dans le futur";
$html .= "<li>Module MAIL : Correction bug : les espaces entre les destinataires ne sont plus supprimées";
$html .= "<li>Module MAIL : Correction bug : les espaces dans les noms des fichiers ne sont plus tronqués";
$html .= "<li>Module MAIL : Correction bug : le nombre de destinataire ne sont plus limités";
$html .= "<li>Module MAIL : Les status des messages sont plus détaillés";
$html .= "<li>Module MAIL : Chaque élément récupéré par un destinataire est horodaté";
$html .= "<li>Module MAIL : Le jour et l'heure où un destinataire a pris connaissance du message sont indiquées et horodatées";
$html .= "<li>Module MAIL : Nouelle présentation des mails reçus";
$html .= "<li>Module MAIL : La gestion du carnet d'adresses a été complètement revue.";
$html .= "<li>Module MAIL : Il est possible de créer des contacts et de les placer dans un ou plusieurs groupes";
$html .= "<li>Module MAIL : Import d'un carnet d'adresses";
$html .= "<li>Module MAIL : Le mot de passe n'est plus indiqué par défaut dans le mail de notification";
$html .= "<li>Module MAIL : Il est possible de stipuler l'adresse mail éméttrice pour l'ensemble des utilisateurs de la collectivité";
$html .= "<li>Module MAIL : Dans le sujet des mails expédiés apparaît entre crochets le nom de la collectivité";
$html .= "<li>Module MAIL : Suppression des menus déroulant au profit d'une saisie semi-automatique";
$html .= "<li>Module MAIL : Des « : » ont été ajoutés après « objet », « message » et « envoyé le »";
$html .= "<li>Module MAIL : La taille de l'ensemble du mail est indiquée";
$html .= "<li>Module MAIL : L'ensemble des pièces jointes n'est plus indiquée par « mail.zip » mais par « Tous les fichiers »";
$html .= "<li>Module MAIL : Mise à jour de la documentation API";
$html .= "</ul>";


$html .= "<h2>V1.0.8.3.7 du 26.08.2009</h2>";
$html .= "<ul>";
$html .= "<li>Hélios : corrections pour respecter l'API webservice";
$html .= "</ul>";


$html .= "<h2>V1.0.8.3.6 du 22.07.2009</h2>";
$html .= "<ul>";
$html .= "<li>Hélios : rajout du SHA1 dans l'export CSV de fichiers reçus     ";
$html .= "</ul>";

$html .= "<h2>V1.0.8.3.5 du 25.06.2009</h2>";
$html .= "<ul>";
$html .= "<li>correction sur la verification de la taille de l'archive pour Actes";
$html .= "<li>amélioration 297 : on affiche le numéro d'actes dans la liste des transactions en cours";
$html .= "<li>amélioration 300 : verification de la validité du numéro SIREN";
$html .= "</ul>";

$html .= "<h2>V1.0.8.3.3 du 23.06.2009</h2>";
$html .= "<ul>";
$html .= "<li>correction des bugs sur ACTES liés à une mauvaise configuration du serveur du MIOCT pour les collectivites Corses. (Bug 308)";
$html .= "<li>correction sur Hélios de la methode de rappatriement du PES de rejet (Bug 302)";
$html .= "<li>correction sur Hélios sur le PES ACK. On interrogeait pas le bon tag dans le XML. (Bug 301) ";
$html .= "</ul>";

$html .= "<h2>V1.0.8.3.2 du 15.06.2009</h2>";
$html .= "<ul>";
$html .= "<li>modification de l'envoi des paramètres à l'applet de signature";
$html .= "</ul>";

$html .= "<h2>(Servlet) V1.0.8.3 du 29.04.2009</h2>";
$html .= "<ul>";
$html .= "<li>changement du mode de connexion utilisé pour le FTP vers la DGFIP  ";
$html .= "</ul>";

$html .= "<h2>V1.0.8.3.1 du 19.03.2009</h2>";
$html .= "<ul>";
$html .= "<li> modification du simulateur ";
$html .= "</ul>";

$html .= "<h2>V1.0.8.3 du 10.03.2009 </h2>\n";
$html .= "<ul>\n";
$html .= "<li>Servlet&nbsp;: corrigé la méthode de log</li>";
$html .= "<li>Module Actes&nbsp;: Corrigé les bugs 265,267,268,269,274,275,276</li>";
$html .= "<li>Module Helios&nbsp;: Modifié la fonction pour se connecter au serveur FTP de la DGFIP</li>";
$html .= "<li>Module Helios&nbsp;: Corrigé le bug 271</li>";
$html .= "<li>Module Mail&nbsp;: Corrigé le bug 273</li>";
$html .= "</ul>\n";

$html .= "<h2>V1.0.8.1 du 16.01.2009 </h2>\n";
$html .= "<ul>\n";
$html .= "<li>Module Helios&nbsp;: Création des APIs pour Helios</li>";
$html .= "<li>Module Helios&nbsp;: Modification du validateur XML</li>";
$html .= "</ul>\n";

$html .= "<h2>V1.0.8.0 du 22.12.2008</h2>\n";
$html .= "<ul>\n";
$html .= "<li>Module Helios&nbsp; : Ajout du module Helios</li>";
$html .= "<li>Module Actes&nbsp;: Mise à jour du module Acte et de son simulateur vers Acte 1.4(en test)</li>";
$html .= "</ul>\n";

$html .= "<h2>V1.0.7.1 du 03.06.2008</h2>\n";
$html .= "<ul>\n";
$html .= "<li>Module Mail&nbsp; : Ajout du Module Mail</li>";
$html .= "<li>Module Admin&nbsp;: Corrigé des bugs Admin;(bug ID:209,196,146)</li>";
$html .= "<li>Module Actes&nbsp;: Corrigé des bugs du module Actes;(bug ID:187,214, 218, 211, 194, 199,197, (219->190))</li>";
$html .= "</ul>\n";

$html .= "<h2>Limitations connues</h2>\n";
$html .= "<ul>\n";
$html .= "<li>Module Actes - Les noms de fichiers transmis ne peuvent contenir de caractères '.</li>";
$html .= "<li>Module Actes - L'objet ne peut pas contenir le caractère spécial &.</li>";
$html .= "<li>Module Actes - Un administrateur de groupe ne peut modifier son profil. Il ne peut créer que des utilisateurs de sa collectivité.</li>";
$html .= "</ul>\n";


$html .= "<h2>V1.0.4 du 13.12.2007</h2>\n";
$html .= "<ul>\n";
$html .= "<li>Module Actes&nbsp;: Fin de la suppression des archives dont toutes les enveloppes sont acquittées.</li>";
$html .= "<li>Module Actes&nbsp;: Ajout de la possibilité d'associer des pièces jointes lors de la création d'une transaction à partir d'un lot</li>";
$html .= "<li>Module Actes&nbsp;: Gestion évoluée des mails de notification d'acquittement</li>";
$html .= "</ul>\n";
$html .= "<h2>V1.0.2 du 16.02.2007</h2>\n";
$html .= "<ul>\n";
$html .= "<li>Implémentation administration 3 niveaux, ajout d'un nouveau rôle «&nbsp;Administrateur de groupe&nbsp;»</li>";
$html .= "<li>Ajout d'une adresse de messagerie pour diffusion d'informations dans les collectivités</li>";
$html .= "<li>Module Actes&nbsp;: ajout possibilité de télécharger les fichiers des transactions (archive totale ou fichiers indépendants) pendant la durée de vie de la transaction</li>";
$html .= "<li>Module Actes&nbsp;: ajout validation/refus par lot des transactions</li>";
$html .= "<li>Module Actes&nbsp;: ajout d'un attribut URL d'archivage pour les transactions de transmission d'acte</li>";
$html .= "<li>Module Actes&nbsp;: ajout traitement par lot des transmissions d'actes</li>";
$html .= "<li>Module Actes&nbsp;: ajout filtre sur dates de postage et d'accusé réception dans la liste des transactions</li>";
$html .= "<li>Module Actes&nbsp;: correction import incorrect des classifications, affichage désordonné et bug javascript lors de la présence de guillemet double</li>";
$html .= "<li>Module Actes&nbsp;: ajout possibilité de désactiver dans la configuration la limitation de une seule demande de classification par jour</li>";
$html .= "</ul>\n";
$html .= "<h2>V1.0.1 du 27.10.2006</h2>\n";
$html .= "<ul>\n";
$html .= "<li>Ajout possibilité de récupérer le fichier XML de la classification matières/sous-matières</li>";
$html .= "<li>Les deux premiers codes de classification matières/sous-matières sont obligatoires</li>";
$html .= "<li>Ajout authentification par login/password vers le ministère</li>";
$html .= "</ul>\n";
$html .= "<h2>V1.0 du 01.10.2006</h2>\n";
$html .= "<ul>\n";
$html .= "<li>Publication initiale</li>";
$html .= "<li>Support complet protocole Actes</li>";
$html .= "</ul>\n";
$html .= "</div>\n";
$doc->addBody($html);

$doc->buildFooter();

$doc->display();

?>
