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

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$html = "<p> On trouvera  après la note de dernière version la liste des limitations connues pour cette version</p>";
$html .= "<h1>Logiciel TéDéTIS - notes de publication</h1>\n";


$html .= "";

// $html .= "<h2>V1.0.8.3 du </h2>";
// $html .= "<ul>";
// $html .= "<li>";
// $html .= "</ul>";
$html .= "<h2>V2.2 du 25.09.2015</h2>";
$html .= "<ul>";
$html .= "<li>API : une nouvelle API permet de tester la connexion à la plate-forme S²LOW ;";
$html .= "<li>Administration : l'authentification par certificat RGS* peut être couplée avec un RGS** ;";
$html .= "<li>Administration : il ne peut plus y avoir d'espace dans l'adresse mail d'un utilisateur ;";
$html .= "<li>Module HELIOS : le système d'analyse et de télétransmission des flux PES a été syndé en deux parties ;";
$html .= "<li>Module HELIOS : les schémas PESv2 ont été mis à jour et récupéré sur Xemelios ;";
$html .= "<li>Modules ACTES et HELIOS : le menu de sélection de la collectivité a été remplacé pour etre plus intuitif ;";
$html .= "<li>Module HELIOS : les PES récupérés erronés sont déplacés dans un dossier spécifique ;";
$html .= "<li>Module ACTES : mise en place de la macro ACTES_MAIL_BACKUP ;";
$html .= "<li>Module ACTES : correction bug : si l'antivirus n'était pas lancé, les flux ne pouvaient pas être analysés et ils passaient en erreur ;";
$html .= "</ul>";


$html .= "<h2>V2.1.01 du 23.06.2015</h2>";
$html .= "<ul>";
$html .= "<li>Module HELIOS : correction bug : la signature HELIOS introduisait un ID dans les bordereaux ;";
$html .= "<li>Module ACTES : correction bug : les signatures des transactions n'était plus incluses dans le cadre de la soumission d'une enveloppe complète ;";
$html .= "<li>Module ACTES : correction bug : l'administrateur de collectivité ne pouvait pas modifier les paramètres de sa collectivité ;";
$html .= "<li>Administration : correction bug : affichage de la date expiration du certificat ;";
$html .= "<li>Module HELIOS : ajout de la macro HELIOS_FTP_PASSIVE_MODE ;";
$html .= "<li>Module HELIOS : pour la signature en locale, les PES sont signés au niveau bordereaux si ils ont des ID ;";
$html .= "</ul>";


$html .= "<h2>V2.1 du 08.06.2015</h2>";
$html .= "<ul>";
$html .= "<li>Module HELIOS : correction bug : erreur d'import des fichiers dont les noms comportaient des caractères spéciaux ;";
$html .= "<li>Module ACTES : correction bug : horodatage du nom de la personne déclenchant la télétransmission ;";
$html .= "<li>Module ACTES : correction bug : l'administrateur de collectivité ne pouvait pas modifier les paramètres de sa collectivité ;";
$html .= "<li>Module HELIOS : changement du message pour l'état Posté ;";
$html .= "<li>Module ACTES : optimisation du mécanisme de vérification des fichiers par l'antivirus ;";
$html .= "<li>Optimisation de la base de données ;";
$html .= "</ul>";

$html .= "<h2>V2.0 du 05.05.2015</h2>";
$html .= "<ul>";
$html .= "<li>Module HELIOS : prise en charge des XML complexes pour la signature ; ";
$html .= "<li>Module ACTES : correction bug : vérification que le fichier PDFTK existe ;";
$html .= "<li>Module ACTES : correction  bug : amélioration vérification des signatures lors de l'import des enveloppes ;";
$html .= "<li>Module ACTES : correction  bug : remise en place du versement par lot ;";
$html .= "<li>Administration : correction  bug : vérification du département et de l'arrodissement via les API ;";
$html .= "<li>Administration : correction  bug : seul le superadmin peut modifier le paramétrage SAE ;";
$html .= "<li>Module ACTES : traitement par lot changement du bouton Envoyer par Créer le lot ;";
$html .= "<li>Module ACTES : ajout de l'identifiant unique dans le tampon ajouté par S²LOW ;";
$html .= "<li>Module ACTES : transmission de la signature électronique de l'acte lors du versement au SAE ;";
$html .= "<li>Module ACTES : permettre à un administrateur decollectivité de verser au SAE ;";
$html .= "<li>Module ACTES : transmission de l'acte tamponne lors du versement au SAE ;";
$html .= "<li>Module ACTES : nouvelle API permettant de récupérer la liste des documents d'une transaction ;";
$html .= "<li>Passage sous Postgres 9.4 ;";
$html .= "<li>Passage sous Openssl 1.0 ;";
$html .= "<li>Passage sous PHP5.5 ;";
$html .= "</ul>";

$html .= "<h2>V1.5.01 du 17.12.2014</h2>";
$html .= "<ul>";
$html .= "<li>Module MAILS : correction bug : si un destinataire est en double dans un même champ on obtient une page blanche ; ";
$html .= "<li>Module MAILS : correction bug : lors de l'ajout d'un contact, le champ description n'était pas pris en compte ; ";
$html .= "<li>Module MAILS : correction bug : le champ CCI n'était pas autocomplété ; ";
$html .= "<li>Module HELIOS : correction bug : les commandes SITE n'étaient pas correctement envoyées ; ";
$html .= "<li>Correction bug : suppression des \n dans les boites de dialogues ; ";
$html .= "<li>Correction de fautes d'orthographe ;";
$html .= "<li>Correction bug : prise en compte du droit choississez dans l'affichage des modules autorisés ;";
$html .= "<li>Module ACTES : agrémentation des informations envoyées à Pastell dans le cadre du versement SEDA ;";
$html .= "<li>Module ACTES : ajout de l'identifiant unique dans le tampon ;";
$html .= "<li>Module HELIOS : sécurisation de l'API de mise à disposition des PES_ACQUIT/ACK/NACK ;";
$html .= "<li>Module HELIOS : amélioration de la regexp d'analyse des retours des commandes FTP ;";
$html .= "<li>Module HELIOS : ajout de la vérification de la taille du PES lors de l'import via API plus contrôle par l'antivirus avant d'accepter le dépot ;" ;
$html .= "<li>Module ACTES : nouvelle API permettant la télétransmission en préfecture via redirection d'URL ;";
$html .= "<li>Module MAILS : les commandes console sont commentées pour garder une compatibilité avec IE ;";
$html .= "<li>Mise en place d'un fichier de configuration générique ;";
$html .= "</ul>";

$html .= "<h2>V1.5 du 05.08.2014</h2>";
$html .= "<ul>";
$html .= "<li>Refonte globale de l'interface web pour être aux normes d'accessibilités ;";
$html .= "<li>Passage sous Bootstrap v3 de l'interface web ;";
$html .= "<li>Module ACTES : ajout de la signature électronique de l'acte (au format PDF) ;";
$html .= "<li>Module ACTES : possibilité d'envoyer un PDF joins à un XML (acte budgétaire) ;";
$html .= "<li>Module ACTES : lors d'une annulation,l'acte principal passe à l'état annulé et la transaction d'annulation passe à l'état acquitement reçu ;";
$html .= "<li>Module HELIOS : optimisation du module ;";
$html .= "<li>Module HELIOS : refonte du système d'envoie des flux PES pour passer de JAVA à PHP ;";
$html .= "<li>Module HELIOS : ajout de la signature électronique du flux PES_ALLER;";
$html .= "</ul>";

$html .= "<h2>V1.4.01 du 14.01.2014</h2>";
$html .= "<ul>";
$html .= "<li>Module ACTES : corection bug : versement SEDA via Pastell en HHTPS;";
$html .= "</ul>";

$html .= "<h2>V1.4 du 09.11.2013</h2>";
$html .= "<ul>";
$html .= "<li>Module MAIL : correction bug : problème d'encodage des API ;";
$html .= "<li>Administration : versement au SAE via Pastell;";
$html .= "<li>Module ACTES : versement par lot au SAE ;";
$html .= "<li>Module ACTES : ajout du nouvel état d'attente via les API pour que les actes puissent être validés par l'agent télétransmetteur ;";
$html .= "<li>Module ACTES : ajout d'un statut permettant de temporiser l'envoie d'un acte aprrès sont dépôt sur le TdT;";
$html .= "<li>Module HELIOS : versement au SAE ;";
$html .= "<li>Module DIA : ajout du module ;";
$html .= "</ul>";

$html .= "<h2>V1.3.2 du 06.05.2013</h2>";
$html .= "<ul>";
$html .= "<li>Module ACTES : nouveau système de notification d'acquittement pour les agents télétransmetteur";
$html .= "</ul>";

$html .= "<h2>V1.3.1 du 15.01.2013</h2>";
$html .= "<ul>";
$html .= "<li>Module ACTES : correction bug : la variable pdfgenerate n'était pas correctement réinitialisée. Cela entrainait une erreur sur le PDF joins aux notifications automatiques ;";
$html .= "<li>Module ACTES : correction bug : il est possible de filtrer les actes sur l'état \"Refus d'envoi\" ;";
$html .= "<li>Module ACTES : correction bug : l'état \"en cours\" prend en compte les transactions aux états \"Document reçu\" et \"Acquittement envoyé\";";
$html .= "<li>Module HELIOS : correction bug : l'émission des flux PESv2 est beaucoup plus rapide et la servlet ne se bloque plus ;";
$html .= "<li>Module HELIOS : correction bug : les administrateurs de groupes ne peuvent plus lister les transactions des collectivités n'appartenant pas à leur groupe ;";
$html .= "<li>Module HELIOS : correction bug : la gestion des droits sur ce module a été revue pour ne plus dépendre du module ACTES ;";
$html .= "<li>Module MAIL: correction bug : protection renforcée sur l'insertion de code dans les champs Nom et adresse mail ;";
$html .= "<li>Module ACTES : utilisation du logiciel pdfsam-console pour rendre les actes au format PDF tamponnables lorsque pdftk ne peut être utilisé ;";
$html .= "<li>Module HELIOS : amélioration de la récupération des PES ACK/NACK en utilisant un script PHP en lieu et place d'une servlet JAVA car cette dernière se bloquait ;";
$html .= "<li>Module MAIL : le navigateur web Mozilla Firefox ne remplit plus automatiquement le formulaire avec les login/mot de passe de l'agent ;";
$html .= "<li>Administration : modification de l'API \"Liste des collectivités\". Il est possible de filtrer sur tout ou partie du SIREN ;";
$html .= "</ul>";

$html .= "<h2>V1.3 du 31.10.2012</h2>";
$html .= "<ul>";
$html .= "<li>Module ACTES : correction bug : les PDFs optimisés sont pris en comptes pour apposer le cartouche/tampon dans les mails de notifications ;";
$html .= "<li>Module ACTES : correction bug : la supression des dossiers temporaires unzip doit être faite après avoir changer de répertoir courrant pour éviter un NOTICE ;";
$html .= "<li>Module ACTES : correction bug : les fichiers avec l'extension .PDF sont tamponés ;";
$html .= "<li>Module ACTES : correction bug : la position du cartouche est fixe sur les documents ;";
$html .= "<li>Module ACTES : correction bug : l'orthographe du mot envoi a été corrigée sur différentes pages ;";
$html .= "<li>Module ACTES : correction bug : le caractère : est en trop sur certaines pages ;";
$html .= "<li>Module HELIOS : correction bug : la colonne suivi indique toujours le même nom ;";
$html .= "<li>Module ACTES : le versement au SAE intègre les courriers Ministèriel lié à l'acte versé ;";
$html .= "<li>Module ACTES : Le traitement par lot a été totalement revu et abandonne JAVA ;";
$html .= "<li>Module ACTES : Les mails de notification de reception d'un courrier Ministèriel sont plus explicites ;";
$html .= "<li>Module ACTES : Dans les détails d'une transaction, l'identifiant de transafert au SAE est indiqué ;";
$html .= "<li>Module ACTES : correction bug : la validation ou le refus d'un acte n'est plus possible si une demande d'annulation est en cours;";
$html .= "<li>Module HELIOS : Les API \"graphique\" ne sont plus présentes dans la page d'import ;";
$html .= "<li>Module MAIL : Le corps du mail de notification envoyé aux destinataires a été reformulé suites aux demandes des collectivités ;";
$html .= "<li>Module MAIL : Le nombre de carcatères autorisés dans le carnet d adresses pour les noms des contactes est passé à 100 ;";
$html .= "<li>Module MAIL : Les champs \"Nom\" et \"Adresse mail\" sont protégés contre l'insertion de code ;";
$html .= "<li>Admnistration : Le nombre de caractères autorisés dans le champs adresse électronique de diffusion d information est passé à 2000 ;";
$html .= "<li>Administration : Modification de l'interface de paramétrage du connecteur SAE ;";
$html .= "<li>Administration : De nouvelles API permettent la gestion des collectivités, des utilisateurs, des groupes ;";
$html .= "</ul>";

$html .= "<h2>V1.2.2 du 01.06.2012</h2>";
$html .= "<ul>";
$html .= "<li>Module ACTES : les PDFs optimisés sont pris en comptes pour apposer le cartouche/tampon";
$html .= "<li>Module ACTES : les adresses mails par défaut ne sont plus décochables";
$html .= "<li>Module ACTES : le bouton versement SEDA s'affiche uniquement lorsque la collectivité est paramétrée";
$html .= "<li>Module ACTES : augmentation du niveau de logs pour les mails de notification envoyés automatiquement";
$html .= "<li>Module MAIL : la limite du nombre de caractères pour les adresses mails du carnet d'adresses est passée de 50 à 100.";
$html .= "<li>Module ACTES : correction bug : la suppression des fichiers temporaires entrainaient un warning dans les logs";
$html .= "<li>Module ACTES : correction bug : lors de la réception d'un courrier Ministèriel, celui est désormais rattaché au propriétaire de l'acte concerné";
$html .= "<li>Module MAIL : correction bug : les sujets des mails dépassant 74 caractères subissaient un problème d'encodage ";
$html .= "<li>Module HELIOS : correction bug : le mot list est remplacé par liste";
$html .= "</ul>";

$html .= "<h2>V1.2.1 du 20.03.2012</h2>";
$html .= "<ul><li>Module ACTES et MAIL : correction bug : modification des entêtes des mails envoyés pour ne plus avoir de BAD HEADER";
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
$html .= "<li>Module ACTES : Les actes sont disponibles avec tampon indiquant la date d'envoi à la prefecture et de réception par celle-ci";
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

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();
