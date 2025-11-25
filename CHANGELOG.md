# Changelog

## 6.0.0

### Évolutions

- Ajout d'un champ pour personnaliser le message lors du passage d'un acte en erreur. #683
- Ajout d'un filtre permettant de trier les utilisateurs par status. #616
- Ajout des liens "Liste des actes de la collectivité" et "Liste des PES de la collectivité" dans la page de gestion d'une collectivité. #1117
- Ajoute la possibilité de filtrer les utilisateurs par certificat valide ou expiré. #617

### Suppression

- Suppression de la prise en charge de la soumission des enveloppes ACTE #1104
- Suppression de l'outil de signature intégré #1113
- Suppression des jetons d'horodatage #1118
- Suppression de `XadesSignature::sign()` et XadesSignatureProperties
- Suppression de la possibilité d'envoyer des mails à partir de S2low via l'API et l'interface. #1116
- Suppression de la possibilité d'enrichir le carnet d'adresse. #1116
- Il est toujours possible de consulter les mails existants.
- Suppression des paramètres de module et du mode papier #1426

## 5.1.7 - 2026-01-12

## Ajouts

- Ajout de Saint-Pierre-et-Miquelon #1443

### Évolutions

- Permettre de choisir la taille de la clé RSA lors de la création d'un certificat via ACME #1469

### Corrections

- Suppression d'appels de fonctions inutiles #1468
- Correction d'un bug empêchant de télécharger un mailsec quand le nom de fichier n'était pas UTF-8. #1460
- Correction permettant de supprimer certains mails inutiles pour les comptes techniques. #1465, #1467
- Correction permettant d'afficher un message d'erreur plutôt qu'une erreur symfony lors d'une tentative de connexion
via login/password dans le cas ou le password n'est pas paramétré. #1466

## 5.1.6 - 2025-12-09

### Corrections

- Prise en compte de la variable WEBMASTER qui permet maintenant au lien vers Webmaster de la page d'accueil et du
footer de rediriger correctement. #1422
- Correction permettant maintenant d'assigner une date de publication à un tampon d'acte. #1407
- Correction d'un script permettant la double lecture d'un mail AR Acte sans le prendre en compte une seconde fois. #1449
- Correction d'un bug lors de l'exécution du worker helios-menage menant à une exception évitable. #1445
- Remettre l'acquittement lors d'un retour au statut Acquittement reçu #1452

### Sécurité

- Suppression d'opérations fichier non nécessaire #1461

## 5.1.5 - 2025-10-27

### Corrections

- Correction d'une exception survenant parfois lors de la suppression d'un fichier via helios-menage car le fichier
n'existait deja plus. #1403
- Correction d'un bug survenant parfois lors de la recuperation d'un fichier dans le cloud causant une exception
critique. #1384
- Correction d'un bug empêchant de télécharger les PES en erreur lorsqu'ils étaient trop volumineux.

## 5.1.4 - 2025-09-17

### Corrections

- Le logrotate ne se lançait pas dans le container
- Correction de la vérification des certificats sur admin_user_edit et des archives actes #1367

## 5.1.3 - 2025-09-04

### Corrections

- Correction d'un bug empêchant le transfert des mails sécurisés vers le stockage cloud. #1397
- Correction d’un problème de redirection entraînant un écran blanc lors du passage d'une transaction Helios en erreur. #1394
- Retrait des espaces présents dans le champ Helios FTP dans l'interface d'administration Helios #1344
- Correction de l'accès à `actes_classification_fetch.php` #1373
- Correction d’un problème d'affichage lors de la suppression d'un mail #1362
- Augmentation du TTR de `ActesAntivirusWorker` pour éviter des messages d'erreur #1393
- Permettre la vérification des CRL pour les marchés publics de nature Contrats, conventions et avenants #1382
- Retire les espaces present dans le champ Helios FTP dans l'interface d'administration Helios. #1344
- Correction de l'acces à actes_classification_fetch.php #1373
- Corrige un probleme d'affichage lors de la suppression d'un mail #1362

## 5.1.2 - 2025-07-23

### Corrections

- Correction de l'affichage des mails sécurisés et d’un problème empêchant l'exécution de certains scripts seuls. #1361
- Utilisation du validca étendu pour vérifier le purpose des certificats #1360
- Correction de la récupération des paramètres booléens depuis les variables d'environnement #1359
- [Actes] Modification du timeout pour l'envoi à la DGCL et, après erreur, du délai avant de renvoyer un fichier ainsi
que de sa priorité #1366  

## 5.1.1 - 2025-07-18

### Évolutions

- Permettre de poster les Pes Acquit Retour #1156
- Permettre de configurer la configuration Apache SSLInsecureRenegotiation #1244
- Afficher un message d'erreur lorsqu'un certificat utilisateur ne semble pas permettre l'authentification #1023
- Connexion possible aux nouveaux serveurs de la DGCL. Ajout de `ACTES_MINISTERE_USE_LEGACY_PROTOCOL` #1207

### Corrections

- Correction de la correspondance simulateur/ftp pour les instances de développement #1240
- Ajout d'un filtre dans l'interface web pour sélectionner seulement des fichiers `.xml` lors d'import de PES aller
dans S2low. #1269
- Correction de la remise à zéro du fichier compteur Helios #1314
- Continuer d'exporter les actes même lorsque des fichiers ne sont pas disponibles dans le cloud #1345
- Correction de /admin/authorities/admin_authority_types.php #1340
- Correction du cron qui lance périodiquement la commande beanstalkd:rebuild-queue #1365
- Correction de la commande worker:process-failed et suppression de la commande Legacy #1364

### Sécurité

- Correction d'un message d'erreur trop verbeux #1246

### Limitations connues

- La configuration du serveur DGCL n'est pas correctement récupérée depuis les variables d'environnement
- Utilisation du validca RGS au lieu du validca étendu pour la validation du purpose du certificat
- Problème d'affichage des mails sécurisés
- Le cron qui lance périodiquement la commande beanstalkd:rebuild-queue ne fonctionne plus
- [eventlistener:process-failed] ne fonctionne pas

## 5.1.0 - 2025-02-12

## Dépréciations

- Outil de signature intégré
- Utilisation des mails sécurisés (remplacés par les mails sécurisés de pastell)

## 5.0.47 - 2025-02-11

### Évolutions

- Permettre de lister les transactions pes aller pour un utilisateur archiviste #1232
- Permettre à l'archiviste de modifier les status PES #1233
- Permettre l'utilisation de `/modules/actes/actes_transac_get_ARActe.php` via API

### Corrections

- Supprimer l'exception envoyée par actes-enveloppe-menage en cas de fichier semblable déjà présent dans le répertoire sans transaction #1239

## 5.0.46 - 2024-11-18

### Évolutions

- [Mail Sécurisé] Transférer les fichiers sans transaction dans un répertoire dédié #971

### Corrections

- Permettre l'envoi du mail récapitulant les transactions helios transmises depuis la console d'administration #1231
- Corriger les redirections #1234

## 5.0.45 - 2024-10-25

### Corrections

- Corriger les paramètres du DatePicker pour permettre l'ajout d'acte #1229
- Corriger les paramètres du DatePicker pour permettre l'édition des fenêtres #1230

### Limitations connues

- Impossible d'envoyer le mail récapitulant les transactions helios transmises depuis la console d'administration

## 5.0.44 - 2024-10-21

### Évolutions

- [Actes] Transférer les enveloppes sans transaction dans un répertoire dédié #972
- Prise en compte de la version 5.24 du schema XSD Pes Aller #1220

### Corrections

- Corriger des paginations dans admin_authorities.php #1218
- Supprimer les headers en doublon #1221
- Corriger les redirections #1222
- Faire apparaitre les dates de début et de fin à la définition des fenêtres Actes et Helios #1196
- Permettre de renvoyer au SAE un acte au statut "Erreur lors de l'envoi au SAE" #1226a
- Utiliser le format de date ISO 8601 comportant le fuseau horaire dans les exports de transactions #1211

### Limitations connues

- Impossible d'ajouter un acte ou d'éditer une fenêtre

## 5.0.43 - 2024-09-18

### Corrections

- Corriger les redirections #1218

## 5.0.42 - 2024-09-16

### Évolutions

- Permettre de transformer une commande legacy en S2lowLegacyCommandInSymfonyContainer instanciée comme un controlleur par Symfony #1127
- Création du rôle "Archiviste" #1199
- Permettre de régler l'agraphage OCSP (OCSP Stapling) par virtual host #1209

### Ajouts

- Ajout du script script/helios/helios_admin_transac_retour_export.php permettant d'exporter la liste des PES_RETOUR #1210

### Corrections

- Eviter le message "Erreur : string(36) "Class "S2lowRedirect" does not exist" #1197

### Sécurité

- Mise à jour des headers HTTP #1212

### Limitations connues

- Problème de redirections qui bloquent en particulier l'identification par login/mdp

## 5.0.41 2024-06-20

### Corrections

- Permettre de créer une collectivité #1194

## 5.0.40 2024-06-19

### Corrections

- N'afficher qu'aux Super Administrateurs la possibilité d'accorder les droits archivistes #1193

### Limitations connues

- Impossible de créer une collectivité

## 5.0.39 2024-06-17

### Évolutions

- Créer la possibilité de changer d'état d'archivage via l'API et création du droit correspondant #1180
- mise à jour de pades-valid vers 1.4.10 #1190
- Prise en compte de la version 5.23 du schema XSD Pes Aller #1186
- Ajout d'une commande de changement d'état pour les transactions Helios #630
- Permettre de configurer la valeur par défaut de helios_use_passtrans via la variable d'environnement HELIOS_USE_PASSTRANS_AS_DEFAULT #1192

### Corrections

- Respecter le temps entre deux lancements de script par CustomizableWorkerRunner même lorsqu'une erreur est rencontrée #1185
- Ajouter le nom du fichier concerné aux erreurs 'Erreur lors de la réception du fichier : Le fichier semble vide' #1177
- Eviter un changement de SIREN arbitraire en cas de changement de groupe lors de l'édition d'un collectivité #1158

### Limitations connues

- Impossible de créer une collectivité

## 5.0.38 - 2024-05-21

### Évolutions

- Permettre de spécifier `max_submission_date` et `min_submission_date` pour `/modules/actes/api/list_actes.php` #1188

### Corrections

- Permettre de convertir les login/mot de passe de l'identification HTTP de l'ISO-8859-1 vers l'UTF-8 en spécifiant CONVERT_API_LOGINS_FROM_ISO #1071
- Permettre de convertir les login/mot de passe contenus dans le nonce de l'ISO-8859-1 vers l'UTF-8 en spécifiant CONVERT_API_LOGINS_FROM_ISO #1071
- Continuer la récupération des PES depuis le serveur DGFiP après un erreur de téléchargement #1183
- Enregistrer les fichiers PES à récupérer depuis le serveur DGFiP dans une file Beanstalkd #1129

## 5.0.37 - 2024-04-03

### Évolutions

- Concaténer les messages d'erreur lors de l'envoi de flux helios au SAE si une erreur de suppression succède à une erreur d'envoi vers Pas #1175

## 5.0.36 - 2024-04-03

### Évolutions

- Faire apparaître les étapes de connection à Openstack et Pastell dans les logs d'envoi des flux helios au SAE #1175

## 5.0.35 - 2024-04-02

### Évolutions

- Faire apparaître explicitement les paramètres id_d et action s'ils existent dans les messages d'erreur Pastell #1173

## 5.0.34 - 2024-04-02

### Évolutions

- Faire apparaître explicitement la méthode appelée dans les messages d'erreur Pastell #1172

## 5.0.33 - 2024-03-16

### Évolutions

- Permettre de configurer le niveau de log Apache via la variable d'environnement APACHE_LOG_LEVEL #1171

## 5.0.32 - 2024-03-22

### Corrections

- Correction du téléchargement des annexes d'un acte lorsqu'une date de publication est sélectionnée #1170

## 5.0.31 - 2024-03-20

### Évolutions

- Création de nouveaux états pour les transactions Actes et Helios dont les fichiers ne sont pas accessibles lors de l'envoi au SAE #1103
- Ajout du Haut-commissariat de Nouvelle-Calédonie et des arrondissements #1086

### Corrections

- Passage en UTF-8 des fichiers d'initialisation de la base de données pour les tests unitaires #1160
- Lorsque le stockage openstack n'est pas configuré, ne plus tenter d'y récupérer un fichier #723
- Repasser à vide la date de publication si aucune n'est sélectionnée #1162

## 5.0.30 - 2024-02-29

### Évolutions

- Remonter dans les logs les erreurs rencontrées lors du téléchargement d'un fichier PES en utilisant le protocole FTP #1157

## 5.0.29 - 2024-02-29

### Évolutions

- Attente avant de récupérer les PES lorsque des PES sont déposés sur le serveur distant et arrêt du script lorsqu'une erreur est rencontrée #1157

## 5.0.28 - 2024-02-26

### Corrections

- Mise en conformité du numéro de patch

## 5.0.27 - 2024-02-21

### Corrections

- Correction de la signature d'actes #1124
- Installer Libersign par défaut #1078
- Correction des statistiques Helios pour l'administrateur de groupe #1119
- Correction de la chaîne d'intégration continue #1133
- Mise à jour d'un certificat utilisé par les tests unitaires #1132
- Remonter plus finement les erreurs de récupération de flux depuis le serveur DGFiP #1130
- Correction d'erreurs remontées par la CI #1135
- Mise à jour des composants javascript TimePicker et DatePicker #999
- Correction d'erreurs css #1142
- Correction d'erreurs html #1143
- Correction d'erreurs javascript #1144
- Correction du formatage des dates du bordereau d’acquittement des actes #1123
- Permettre de supprimer helios_ftp_dest lors de l'édition d'une collectivité #1150
- Permettre de supprimer l'adresse électronique métier, l'adresse électronique de diffusion par défaut et l'adresse électronique de diffusion d'informations #937
- Rendre cohérente la variable d'environnement CONVERT_API_LOGINS_FROM_ISO #1097
- Correction du script de récupération des autorités de certification du ministère de l'intérieur #1096
- Gestion des configurations des connexions DGFiP depuis le container de services #1147
- Simplification du script migre_postes_comptables.php #1120

### Évolutions

- Ajout des étapes code_quality et container_scanning dans la CI #1135

### Sécurité

- Correction de faille de sécurité #1140

## 5.0.26 - 2023-12-11

### Évolutions

- Différencier les modes d'envois dans le mail des transmis non acquittés #1114

## 5.0.25 - 2023-11-30

### Évolutions

- Permettre de configurer SSLProtocol par une variable d'environnement #1109

### Corrections

- Correction de la vérification des signatures détachées lors de l'import d'enveloppes #1110

## 5.0.24 - 2023-10-24

### Évolutions

- Permettre de configurer SSLCipherSuite par une variable d'environnement #1098

## 5.0.23 - 2023-10-19

### Sécurité

- Mise à jour des CipherSuites #1095

## 5.0.22 - 2023-10-09

### Évolutions

- Permettre d'utiliser letsencrypt pour le domaine mail sécurisé #1090
- Ajouter la taille et le sha1 du PES Retour à l'export CSV #1091
- Correction du script actes_admin_transac_export.php #1093

## 5.0.21 - 2023-10-03

### Évolutions

- Mise à jour du certificat du serveur DGCL #1085

## 5.0.20 - 2023-10-02

### Corrections

- Correction du script permettant de migrer les postes comptables #1069
- Correction de l'encodage de api-list-login.php #1073
- Permettre de prendre en compte le département dans le script actes-send-classification-for-all-authorities-php #1070
- Empêcher une erreur de bloquer un cron #1067
- Permettre de convertir les login/mot de passe de l'ISO-8859-1 vers l'UTF-8 en spécifiant CONVERT_API_LOGINS_FROM_ISO #1071
- Correction du script monitoring_actes_pdf-stamp.php #1087
- Correction de failles de sécurité #1088

### Évolutions

- Permettre de désactiver helios-reception-passtrans #1068

## 5.0.19 - 2023-09-12

### Corrections

- Mise à jour de la version de pades-valid afin de corriger un problème de vérification de signature

## 5.0.18 - 2023-09-07

### Évolutions

- Ajout de la directive ACTES_IMAP_OPTIONS permettant de modifier les options de connexion à un serveur IMAP #1082

## 5.0.17 - 2023-08-04

### Évolutions

- Prise en compte de la version 5.21 du schema XSD Pes Aller modifié par la DGFiP #762

## 5.0.16 - 2023-07-20

### Corrections

- Sur la file helios-envoi, éviter les messages beanstalkd 'The last XXX seconds were executed outside of the lock' #627

## 5.0.15 - 2023-07-20

### Évolutions

- Ajout du monitoring des jobs présents dans la file 'helios-reception' #1046

### Corrections

- Eviter les messages beanstalkd 'socket timed out' #1046

## 5.0.14 - 2023-07-18

### Corrections

- Correction du worker actes-menage-enveloppe.php #1051
- Utilisation du logger dans les crons helios-envoi et helios-analyse-fichier-a-envoyer #1061

## 5.0.13 - 2023-07-18

### Évolutions

- Permettre de régler le nombre de processus helios-envoi et helios-envoi-passtrans #1055
- Créer un container pour webpack #1053
- Permettre de distinguer dans la commande stats:monitoring-metier les envois Gateway et Passtrans #1057

### Suppressions

- Suppression de Libersign 1 #1053

### Corrections

- Correction dans .env.dist #1051
- Correction markdownlint dans CHANGELOG
- Permettre à nouveau à l'administrateur de collectivité de modifier sa collectivité #1044
- Récupérer le PesAcquit depuis le cloud lors d'un appel de helios_transac_get_status #1045
- Ajout de LIBERSIGN_INSTALLER dans docker-compose.yml #1015

### Sécurité

- Correction de failles de sécurité #799

### Limitations connues

- Le worker actes-menage-enveloppe.php ne se lance plus

## 5.0.12 - 2023-06-23

### Évolutions

- Ajouter le volume de transaction au monitoring Helios #1048

### Corrections

- Permettre à nouveau à l'administrateur de groupes de créer des collectivités #1044

### Limitations connues

- L'administrateur de collectivité ne peut plus modifier sa collectivité

## 5.0.11 - 2023-06-19

### Évolutions

- Permettre la connexion à Passtrans #1024
- Permettre l'export du carnet d'adresse d'une collectivité #1033
- Mettre à disposition un script de monitoring des analyses et envois actes et helios #793

### Corrections

- Récupérer les erreurs lancées lors de l'analyse des fichiers à envoyer pour éviter les blocages #1029
- Remplacement des scripts check-ftp.php et test-helios-connection.php par helios-analyse-fichier-a-envoyer#1035

### Sécurité

- Correction de failles de sécurité #799

### Limitations connues

- L'administrateur de groupe ne peut plus créer de collectivités
- L'administrateur de collectivité ne peut plus modifier sa collectivité

## 5.0.10 - 2023-05-17

### Évolutions

- Intégrer la purge des flux hélios dans supervisor #753
- Utilisation de timeout pour limiter les blocages des scripts lancés par supervisor #979
- Prise en compte de la version 5.20 du schema XSD Pes Aller #1025
- Adaptation du schéma XSD des actes à la réalité de l'envoi par la DGCL #1017

### Corrections

- Corriger l'encodage utilisé lors de l'envoi vers le SAE #1013
- Gérer le cas ou le fichier PES Aller n'est pas trouvé #1028
- Allonger à 4 minutes le délai minimum entre deux passages de récupération FTP #1018
- Spécifier un retour pour cron:helios-envoi #1022

## 5.0.9 - 2023-03-21

### Évolutions

- Remonter les messages d'erreur d'analyse des fichiers reçus du ministère #1009
- Mise à jour du schéma XSD des actes #1007
- Permettre de différencier les notification et le tampons des environnements de test/dev/formation #742

### Corrections

- Prendre correctement en compte les accents dans les noms des pièces jointes des mails sécurisés #1008
- Afficher le temps de chargement des pages #976
- Homogénéiser le contrôle des espaces pour les siren #691
- Remplacement de la constante ALWAYS_USE_EXTENDED_VALIDCA par ONLY_USE_VALIDCARGS #1012

### Suppressions

- Suppression du code et des macros correspondant à la signature technique #584
- Suppression du module DIA #1002

## 5.0.8 - 2023-02-13

### Évolutions

- Limiter à 100 le nombre d'envois simultanés de fichiers au SAE d'une même collectivité #575
- Mise à jour de la bibiliothèque tdt-lib-actes #1003

### Corrections

- Passer par l'antivirus un acte créé avec "signer avant d’envoyer" puis "déposer sans signature" #755
- Homogénéisation de la valeur par défaut de USE_LEGACY_SECURE_MAIL_FIELDS à false #938
- Ajouter un message d'erreur lorsque le CodCol d'un PES Aller est trop long #821
- Verifier que les paramètres d'entrée de la classification sur actes_transac_create.php sont des entiers #668
- Correction et homogénéisation des titres des écrans de visualisation des détails d'un acte et d'un flux helios #928

### Suppressions

- Suppression de la constante ACTES_TYPE_PAR_NATURE #608

## 5.0.7 - 2023-02-06

### Corrections

- Correction de l'ordre et de la description du traitement par lots #997

### Évolutions

- Changement d'ordre des virtuahost pour les applications ne supportant pas le SNI #998

## 5.0.6 - 2023-02-02

### Évolutions

- Ajout du script pour les statistiques de groupes  #992

### Corrections

- Les pièces jointes des actes ajoutées par l'API sont correctement prises en compte #991
- Permettre de configurer ACTES_COMMON_BROADCAST_EMAILS #994
- Permettre de préciser l'utilisation de l'API via get pour actes_transac_create.php pour assurer la rétrocompatibilité avec la v4.3 #993
- Prendre en charge l'encodage UTF-8 dans les noms de pièces jointes des mails #995

## 5.0.5 - 2023-01-30

### Corrections

- Passage de WEB_HTTPS_PORT dans docker-compose.yaml pour permettre la redirection Apache #987
- Permettre de charger les ressources js et css depuis S2LOW_WEBSITE_MAIL #988

### Limitations connues

- Les pièces jointes des actes ajoutées par l'API ne sont pas correctement prises en compte #991
- Certaines pièces joindes des mails ne s'ouvrent pas #995

## 5.0.4 - 2023-01-27

### Corrections

- Les mails sécurisés passent désormais par HTTPS
- Correction d'un problème d'accent dans le nom des fichiers actes.
- Le pager ne fonctionnait pas sur le mail sécurisé
- Redirection de la page d'accueil pour éviter une boucle de redirection

### Limitations connues

- Les mails envoyés avant le passage sur S2LOW_WEBSITE_MAIL ne s'affichent pas correctement
- Les ressources js et css ne sont pas correctement chargées depuis S2LOW_WEBSITE_MAIL

## 5.0.3 - 2023-01-27

### Corrections

- Changement de la mémoire allouée au service pdf-stamp #980
- Modification du répertoire de la configuration Symfony pour les scripts #978
- Correction de la lecture du DN des certificats #981
- Si le nom de la collectivité contenait un accent, il était impossible signer les PES directement avec Libersign

## 5.0.2 - 2023-01-23

### Suppressions

- Le protocole TLSv1.3 est désactivé sur Apache2 pour rester compatible avec les certificat RGS**.

## 5.0.1 - 2023-01-22

- Ajout d'un Makefile de production pour gérer les règles iptables pour l'accès DGFip
- Modification de la valeur par défaut du P_DEST pour le protocole Helios

## 5.0.0 - 2023-01-20

### Évolutions

- Passage en PHP 8.1 / Ubuntu 22.04 et adaptation du code
- Passage en UTF-8 et adaptation du code
- Modification de la licence de Cecill version 2 vers AGPL version 3
- Docker : utilisation de Nexus pour tous les services
- Docker : harmonisation des valeurs par défaut en s2low.docker.libriciel.net
- Docker : changement des valeurs tedetis
- Docker : script de migration des versions de PostgreSQL

### Corrections

- CHANGELOG : corrections sur les sauts de lignes sous les (sous-)titres (markdownlint MD032)
- Le libellé a été ajouté à la classification envoyée vers pastell #807
- Téléchargement depuis le cloud si un PES retour demandé par API n'est pas présent en local #709
- Prise en compte de l'erreur de téléchargement depuis le cloud résultant en un fichier vide #819
- Ajout d'un noindex sur toutes les pages pour éviter l'indexation par les moteurs de recherche #875
- Permettre de mettre des espaces dans la recherche des collectivité par SIREN #867
- Prise en compte du délai de 4 heures dans le lien "Actes transmis depuis plus de 4 heures" de la console d'administration #852

### Suppressions

- les constantes suivantes ont été supprimées, car obsolète : OPENSIGN_WSDL, OPENSIGN_CA, OPENSIGN_CRT,
OPENSIGN_TIMEOUT, ACTES_TYPE_PJ_IS_MANDATORY, DB_CLIENT_ENCODING, PHP_UNIT_AUTOLOADER, MODE, ETAT_CIVIL_FILES_UPLOAD_ROOT,
IMAP_LOGIN, IMAP_PASS
- les constantes suivantes ont été supprimées, car elles étaient dépréciées : VERIFICATION_SIREN
- les constantes suivantes ont été supprimées, car elles ne sont plus nécessaires du fait du passage en docker :
XML_STARLET_PATH, MODE_BEANSTALKD, MODE_REDIS
- Desactive le lancement de l'export OCRE par supervisor

## 4.3.19 - 2022-09-19

### Sécurité

- Correction de failles de sécurité #799

### Corrections

- Changer le contenu du tampon de "Affiché le" pour "Publié le" #797
- Corriger la vérification des signatures avec une URI vide #825
- Correction de l'affichage des options du traitement d'actes par lot #823

## 4.3.18 - 2022-06-13

### Corrections

- Empêcher l'envoi vers Pastell de doublons lors de l'archivage (module Helios) #355
- Amélioration de la gestion du lancement des processus extérieurs #750
- Suppression des fichiers temporaires générés lors de la vérification des signatures XADES #804

## 4.3.17 - 2022-05-17

### Évolutions

- Passage de php:7.2-apache-stretch vers ubuntu:18.04 pour l'image de base Docker

### Corrections

- Rétablir le fonctionnement de la création de collectivité #803

## 4.3.16 - 2022-05-09

### Évolutions

- Vérification de la non-corruption du pdf dans ActesAnalyseFichierAEnvoyerWorker #756
- Création d'un script permettant de modifier le domaine de l'adresse email pour un groupe #693

### Corrections

- Utilisation de l'heure générée par le serveur applicatif pour créer les nonces #791

### Sécurité

- Correction d'une faille de sécurité #774
- Correction d'une faille de sécurité #775
- Correction d'une faille de sécurité #776
- Correction d'une faille de sécurité #778
- Correction d'une faille de sécurité #779
- Correction d'une faille de sécurité #780
- Correction d'une faille de sécurité #781
- Correction d'une faille de sécurité #782
- Correction d'une faille de sécurité #783
- Correction d'une faille de sécurité #784
- Correction d'une faille de sécurité #786
- Correction d'une faille de sécurité #787
- Correction d'une faille de sécurité #788
- Correction d'une faille de sécurité #789
- Correction d'une faille de sécurité #790

## 4.3.15 - 2022-03-24

### Évolutions

- Possibilité de poster les CompteFinancierUnique #772

## 4.3.14 - 2022-03-21

### Évolutions

- Prise en compte de la version 5.17 du schema XSD Pes Aller #762

## 4.3.13 - 2022-03-08

### Corrections

- Correction d'un problème d'accès à l'annuaire #751

## 4.3.12 - 2022-01-17

### Évolutions

- Suppression de l'état "En traitement" pour les transaction Helios pour éviter les blocages #625
- Mise à jour du simulateur en 1.1.2 #748

### Corrections

- Corection d'une erreur sur helios-pes-acquit-menage #293
- Correction d'une vulnérabilité #731
- Correction d'une vulnérabilité #732
- Correction d'une vulnérabilité #733
- Correction d'une vulnérabilité #734
- Correction d'une vulnérabilité #735
- Correction d'une vulnérabilité #736
- Correction d'une vulnérabilité #737
- Correction d'une vulnérabilité #738
- Correction d'une vulnérabilité #739
- Correction de vulnérabilités XSS #744
- Ajout d'un message d'erreur lorsque le nombre d'annexes à un acte est trop important #747

## 4.3.11 - 2021-12-03

### Évolutions

- Remplacement des releases notes codées en dur par un affichage du Changelog #722
- Création d'un worker pour récupérer les CRL #728

### Corrections

- Correction du répertoire de récupération des CRL dans le Docker #728
- Ajout d'un timeout à l'envoi au SAE #713

## 4.3.10 - 2021-10-11

### Corrections

- Correction de l'extraction des certificats comportant un saut de ligne en début ou fin depuis les flux helios #714

## 4.3.9 - 2021-06-24

### Évolutions

- Ajout d'une API de statistique actes pour les admins de groupe #610
- Prise en compte de la version 5.14 du schema XSD Pes Aller #696
- Prise en compte de la date de la signature lors de la validation du certificat associé pour les actes #486
- Ajout d'une API de statistique actes et les pes aller pour les admins de groupe #610
- Prise en compte de la date pour la vérification des signatures Xades Pes aller #486
- Explicitation de l'erreur rencontrée lors de la vérification de la signature dans le cas Xades Pes aller #707
- Ajout des nouveaux codes de nature de collectivité fournis par la DGCL #712

### Corrections

- Correction du mail sécurisé pour diminuer les risques d'être classé comme spam #698
- Le message dans le journal lors de la confirmation de postage était incomplet #694
- Clarification du message d'erreur lorsque le certificat d'un document signé n'est pas reconnu #624

## 4.3.8 - 2021 04 07

### Corrections

- corriger l'identification par nounce #690

## 4.3.7 - 2021-03-23

**Cette version demande une modification de la base de données**

### Corrections

- les scripts de supervision notifient le mail EMAIL_TECHNIQUE #680
- permettre de traiter les messages liés à un acte même si le cloud n'est pas accessible #685
- les actes dont l'archive est en erreur ne bloquent plus actes-notification.php #684

### Evolution

- le script de supervision du service pades est compatible avec les codes retour Nagios #680
- changement d'encodage du mot de passe

## 4.3.6 - 2021-02-11

**Cette version demande une modification de la base de données**

### Corrections

- ajout d'index pour accélérer le traitement du ménage des fichiers présents dans le cloud
- passage du temps minimum d'éxécution des WorkerScript de 1s vers 10s

### Evolution

- déplacement des PES Aller non liés à une transaction dans le répertoire HELIOS_PESALLER_SANSTRANSACTION
- création d'un groupe supervisor mailsec-menage contenant les scripts cloud des mails #245
- Intéger la valeur du codcol dans le mail listant les transactions Helios restées à l'état transmis #675

## 4.3.5 - 2020-12-14

**Cette version demande une modification de la base de données**

### Corrections

- refactoring du ménage des actes envoyés sur le cloud
- correction du ménage des mails sécurisés pour prendre en charge les systèmes très chargé, supression des répertoires de base #666
- ajout de la colonne pes_retour:not_available afin de faire fonctionner le versement dans le cloud
- correction lorsqu'un objet est incorrectement marqué comme non disponible sur le disque #665  
- levée de certaines restrictions sur les certificats des signatures PKCS7 (chaine de confiance, purpose) #667
- retrait des script IWorkerAlwaysLaunch laissé au contrôle de supervisord

### Evolution

- mise à jour de pades-valid vers 1.4.7 #223
- sauvegarde des mails dont le corps est vide #644

## 4.3.4 - 2020-11-17

### Evolution

- mise à jour de la bibiliothèque tdt-lib-actes
- permettre les signatures autosignées et non RGS #622

## 4.3.3 - 2020-11-06

### Corrections

- refactoring de la gestion des services pour éviter des failles de sécurité potentielles #657
- refactoring DataObject pour utilisation de requête préparé afin de combler des failles de sécurité potentielles

## 4.3.2 - 2020-11-02

### Evolution

- limitation de la taille et du nombre des messages d'erreur lors d'une erreur d'envoi sur le cloud #615

### Ajout

- Script bin/console log:timestamp-token-extract-and-delete qui permet de suprimer les timestamps de la table logs_historique après les avoir sauvegardés dans un répertoire

### Corrections

- refactoring de la chaîne d'intégration continue

## 4.3.1 - 2020-09-29

### Corrections

- Correction typo dans le init.php pour la variable helios_sending_mode_demo

## 4.3.0 - 2020-09-16

### Corrections

- Correction bug particulier pour les purges

### Evolution

- Ajout de la variable d'environnement HELIOS_FTP_CONNECTION_MODE permettant d'utiliser le protocole PassTrans #623
- Ajout d'un script qui va passer à l'état erreur des flux PES vieux de plus de tant de jour #619

## 4.2.3 - 2020-07-13

### Corrections

- Ajouter un rollback du changement d'état de la transaction lorsqu'un fichier PES ne peut être déplacé #631
- Les versements actes SAE n'était plus assurés (problème sur le bordereau) #639

### Evolution

- Passage au schéma PES version 5.12

### Ajout

- Ajout du script script/postrgrsql/add-column-not-null-with-default.php permettant l'ajout de colonne non null à chaud

## 4.2.2 - 2020-06-11

### Evolution

- Amélioration de la construction du docker
- Possibilité d'utiliser un nouveau modèle de bordereau (Création de la constante USE_LEGACY_BORDEREAU_MODEL) #100

### Corrections

- Permettre la récupération dans le cloud des fichiers comportant deux // #628

## 4.2.1 - 2020-04-14

### Evolution

- Openstack : tentative de reconnexion lors d'une erreur #591

### Corrections

- Un bug empechait l'envoi correcte des transactions actes avec au moins une annexe sur le SAE sur Pastell V3 #516

## 4.2.0 - 2020-03-09

**Cette version nécessite une modification de la base de données**

### Evolution

- Mise à jour du schéma XSD PES v2 en version 5.11
- Amélioration du script de récupération des classification #535
- Possibilité de dissocier les configuration cloud actes et PES (création de constante `ACTES_OPENSTACK*` et `HELIOS_OPENSTACK*`, rétrocompatible avec l'existant) #534
- L'envoi des PES et des actes dans le cloud passe maintenant par beanstalked #508
- Refactoring des file de traitement (ajout de sections critiques synchronisés avec Redis)
- Ajout des constantes : MODE_REDIS, REDIS_SERVER et REDIS_PORT (gestion du cache Redis utiliser comme gestionnaire de verrou)
- Refactoring des catch des signaux SIGTERM pour la terminaison propre des files de traitement
- Refactoring du ménage des fichiers helios (si c'est sur le cloud, on ne garde que 15 jours de fichiers)
- Les fichiers actes/helios non-disponible ne font plus partie de la boucle d'envoi au cloud
- Envoi des fichier PES Acquit dans le cloud
- Renommage des fichiers du certificat d'horodatage de tedetis_timestamp_XXX en s2low_timestamp_XXX  
- La constante IMAGE_FOR_STAMP a été ajoutée. Elle permet de configurer l'image utilisée pour les tampons. #522
- Création de groupes supervisor  #524
- Le mail d'alerte sur le nombre d'actes en "En attente de transmission" n'est désormais envoyé que si aucun acte n'a été transmis dans les 10 dernières minutes. #495
- Les mails sécurisés peuvent être envoyé dans le cloud #550
- La constante WORKSPACE_DIRECTORY a été ajoutée. #523
- Un message d'erreur empêche d'uploader un PES aller vide #547
- Le lien vers les documents et la date de réception on été supprimés du mail de notification d'erreur #530
- Ajout d'un script permettant de changer le statut d'une transaction actes
- OpenStack : Passage de l'Identity API # 2.0 à l'Identity API v3.0.  Toutes les constantes **_OPENSTACK_AUTHENTICATION_URL_V2 sont a remplacer par** _OPENSTACK_AUTHENTICATION_URL_V3 #574

### Corrections

- Les noms de fichiers envoyé au SAE ne doivent pas être égaux sans tenir compte des accents
- L'absence de typage de la réponse au courrier simple entrainait un bug dans l'API de listage des réponses (qui prenait en compte les messages 2-2)
- Le script d'envoi des PES au SAE se limite au 100 premier PES afin d'éviter que le jeton de cloudwatt n'expire.
- Envoi de la typologie vers Pastell afin de pouvoir envoyer des actes vers un Pastel V3 #546
- Dans certains cas très rares, les transactions Helios n'était pas envoyées #551
- Suppression d'un warning lors de l'utilisation de admin_user_edit_handler #583
- Actes :  gestion des cas ou l'enveloppe n'est trouvee ni en local ni dans le cloud #569

### Retrait

- la constante HELIOS_VALIDATION_UPSTART_TOUCH_FILE a été retirée

## 4.1.0 - 2019-07-08

**Cette version contient principalement des améliorations sur les versements SAE.**

### Evolution

- Les statistiques d'envois au SAE passent sur une page dédiée
- Ajout de bouton permettant de modifier l'état des transactions envoyé au SAE en cas d'erreur
- Les fichiers PES ALLER et actes sont supprimé dès qu'on les a envoyé sur le SAE (s'ils existente sur le cloud)

### Ajout

- Ajout de l'arrondissement 9 pour le département 75 (SGAR) #503
- Ajout de bouton sur les transactions actes et helios permettant d'envoyer et de vérifier les données sur le SAE (pour le superadmin)
- Ajout de statistiques pour l'envoi au SAE (actes/helios)

### Corrections

- Le rebuild-queue n'est plus nécessaire en cas de défaillance des serveurs de la DGCL #501
- le script de récupération des ar du sae échouait si le message d'erreur du SAE dépassait 512 octets #498
- Possibilité d'envoyer sans erreur au SAE les actes contenant des fichiers avec des noms identiques

## 4.0.3 - 2019-06-03

### Corrections

- Les messages de la DGCL indiquant un retour de multicanal sont supprimés au lieu d'être mis en erreur #490
- Réintroduction de la possibilité de déclencher manuellement une notification #494
- Il est possible de télécharger le document tamponné si celui-ci est un effectivement un fichier PDF (was : se termine par .pdf) #485
- Toutes les enveloppes non-envoyées à cause de la pause d'une fenetre sont remises dans la queue benstalked une fois la fenêtre supprimée. #479
- Suppression du bouton "Annuler" sur la signature étant donné que ce bouton n'a pas de sens dans ce contexte #472
- Les dates de recherche de mail sécurisé sautait en cas de nouvelle recherche #447
- Il n'est plus possible pour un admin de groupe de passer une transaction PES Retour à l'état lu #445
- Il est possible que la DGFip dépose des fichier PES Retour dont la taille est supérieur à 150Mo, ces fichiers ne sont pas analysables par s2low [HOTFIX]

### Ajout

- Ajout du nom de la collectivité sur le tableau de résumé d'une transaction actes
- Ajout du lien vers la collectivité et vers l'utilisateur sur le tableau de résumé d'une transaction actes.
- Ajout d'une boîte de confirmation lors de la supression des mails sécurisés. #456
- Journalisation de la supression de mail sécurisé #434
- Ajout du script script/actes/actes-export.php permettant d'exporter les enveloppes et leur acquittement
- Ajout du script script/helios/helios-export.php permettant d'exporter les PES Aller et leur PES Acquit
- Ajout des statistiques d'envoi SAE Actes
- Automatiser les versements sur le SAE en fonction d'un identifiant de transaction minimum et maximum #499

### Evolution

- les PES Aller ne contenant ni bordereau de dépense, ni titre de recette, ni PJ, ni marché ne sont pas transmis et génère une erreur #496
- Les SIREN du fichier d'import peuvent maintenant contenir des espaces (n'importe où) #475
- Possibilité de supprimer la dernière pièce jointe sur le formulaire de création d'un acte #474
- Lors de la création d'une enveloppe de classification, l'utilisateur est redirigé vers la transaction contenant cette demande #466
- Affichage d'un message d'erreur lorsqu'on saisie un mauvais mot de passe sur le mail sécurisé #454
- Légère modification esthétique dans la création d'un mail sécurisé #449

### Dépréciation

- La méthode d'authentification avec certificat utilisateur + certificat RGS** est déprécié et il n'est plus possible
de la sélectionner lors de la création ou de la modification d'un utilisateur. #491

### Suppressions

- Suppression de la classe maison Logger remplacée par S2lowLogger basé sur Monolog

## 4.0.2 - 2019-04-09

### Corrections

- HOTFIX : correction du script de migration de base de données sur certain index récalcitrants
- Oublie du typage des réponses aux réponses des préfectures (Courrier simple, lettre d'observations et demande de pièces complémentaires) #492

## 4.0.1 - 2019-04-02

### Ajouts

- La constante ACTES_TYPE_PAR_NATURE permet d'utiliser la nouvelle notice de typologie des actes (typologie par nature, plus de filtre par classification, supression de 99_AU)
- La constante ACTES_TYPE_PJ_IS_MANDATORY évolue pour implémenter la nouvelle notice

### Corrections

- Il était possible d'intégrer des SIREN non numériques. #484
- Problème de signature si les documents actes ont déjà été envoyé dans le cloud

### Supression

- Supression du Dockerfile PHP 5.5, des tests automatisé sur PHP 5.5 et de fait, supression effective de la compatibilité PHP 5.5

## 4.0.0 - 2018-02-25

### Evolutions

- Le fichier Dockerfile de base est basé sur PHP 7.2
- Mise à jour de pades-valid en version 1.3.0 (docker-compose)

### Corrections

- Remplacement de la bibliothèques tedivm/fetch par php-imap/php-imap pour la récupération des réponses actes afin de corriger le problème des mails non récupérér correctement #420
- Correction de sécurité (XSS possible sur la visualisation de ces propres mail sécurisé) #451
- Supression de la consultation de la boite au lettre tedetis sur la consultation d'un mail sécurisé #448

## 3.0.18 - 2018-12-12

### Ajouts

- Ajout du contrat de license (fichier LICENSE)
- Ajout de log plus pertinent sur l'envoi à Pastell ~actes #427
- Suppression du document sur Pastell en cas d'erreur ~actes #425
- Modification du statut du document si le document sur Pastell est en erreur ~actes #424
- Envoi des transactions au SAE dans l'ordre chronologique ~actes #428
- Ajout d'une case à cocher pour l'envoi automatique au SAE ~actes #429
- Console d'administration, ajour des état SAE ~actes #431
- Permettre le postage d'une transaction en mode synchrone ~actes #426
- Prise en compte des PES acquit non valide et sans codcol

## 3.0.17 - 2018-11-20

***Cette version nécessite une modification de la base de données**

### Evolutions

- Préparation de la compatibilité avec la version 7.2 de PHP.
- Mise à jour de la librairie FPDF (génération des bordereaux) (1.53 -> 1.81)
- Les transactions envoyées au SAE sont considérées en erreur dès le premier envoi et pas au bout de 24 heures d'essai
- Modification de l'interface de connexion à Pastell #416
- Les scripts actes-envoi-sae et helios-envoi-sae prennent un argument facultatif avec l'identifiant de la collectivité #403
- Récupération des PES Acquit qui ne respectent pas le schéma PES v2, mais qu'ont peut raccrocher à un NomFic ~helios #415
- Modification du lien permettant de récupérer les PES Acquit (en bas du cycle de vie) ~helios
- Ajout de la constante ACTES_TYPE_PJ_IS_MANDATORY permettant de rendre obligatoire le typage des enveloppes ~actes #313
- Vérification du code du type des PJ vis à vis de la liste récupéré dans la classification ~actes #419
- Le script cron/jour.php est remplacé par le script script/actes/actes-send-classification-for-all-authorities.php ~actes #308
- Si un message métier d'anomalie est reçu alors que la transaction n'est pas à l'état transmis, on ne change pas l'état de la transaction et on met le message dans la banette des erreurs ~actes #305
- Possibilité de spécifier le type de la pièce principale sur le traitement par lot ~actes #318
- Refus des PES Aller qui ne sont pas en ISO-8859-1 ~helios #373
- Possibilité d'envoyer des mails aux membre d'un groupe déterminé #107

### Corrections

- Correction d'un bug sur la signature local via un certificat contenant un caractère non-ANSI
- Correction d'un bug sur la signature local par lot pour les PES ~helios #414
- Correction du pespolicyhash sur la signature incorrect ~helios #417
- Correction de problème d'envoi en double de transaction en mode beanstalked dans les cas de reconstruction de la job queue #408
- Dans le traitement par lot, les caractère accentué sur les noms des fichiers sont remplacé par des - ~actes #353
- Toutes les adresses emails lors de l'édition d'une collectivité sont maintenant validé #57
- La signature de certaines transactions ne se faisait pas sur le bon document ~actes #323
- Correction d'un problème sur la réception des message de type déféré TA ~actes
- La typologie des pièces par défaut pour les contrat convention et avenant passe de 99_CO à 99_DC et 99_AT à 99_AR ~actes #423

### Ajouts

- Ajout d'un lien vers <http://dss.nowina.lu> pour validation de signature #385
- Validation des fichiers XML et des signatures PADES des actes pour le super admin ~helios  
- Export de la liste des collectvités pour le super admin ou l'admin de groupe #300
- Possibilité de tester les certificats (PEM) pour valider s'ils sont RGS et/ou utilisé sur la plateforme #366
- Ajout d'un bouton permettant de poster l'acte sans le signer s'il est en attente de signature #374
- Possibilité de bloquer des numéro SIRET afin d'éviter les erreurs d'acheminement des PES Retour ~helios #372
- Création du script script/actes/actes-close-old-transactions-transmises.php permettant de clore les transactions transmises depuis plus de 30 jours #344

### Retraits

- Retraits de DBUnit qui était top lent pour la suite de test de s2low
- Supression du répertoire tools devenu obsolète
- Suppression de ce qui est lié au tampon PDF en PHP avec Zend (remplacé par PDFStamp)
- Suppression du fichier build.xml pour Jenkins

## 3.0.16 - 2018-10-08

### Ajouts

- Le lien des pré-requis est disponible également en HTTPS #411
- Passage au schéma PES V2 5.7 ~helios

### Corrections

- la taille du login est désormais limité à 128 caractères sur le formulaire #410

## 3.0.15 - 2018-09-18

***Cette version nécessite une modification de la base de données**

### Evolution

- Passage de la signature Libersign de sha1 à sha256 #170
- Script de changement des arrondissements de la Moselle (script/migration/nouveau-arrondissement-moselle-57.php)
- Le super admin peut passer un transaction à "Posté" ou "En attente de transmission" au lieu du "Revenir en arrière" peu clair ~actes
- Les utilisateurs peuvent visualiser les ARActes. ~actes #383
- Vérification de l'identifiant de l'application (quadrigramme) avec EACT par défaut ~actes #397
- On ne vérifie pas la chaine de certification des signatures de pièces de marchés publics (nature 4, classification 1.1) ~actes #400  
- Les versements SAE se font par ordre chronologique et plus aléatoirement #402

### Corrections

- Les mails sécurisés ne pouvaient pas être supprimé. #388
- Correction de lien vers le bandeau dans le mail sécurisé
- Si on arrive pas à sauvegarder un attachment (fichier accentué par exemple), on ignore le fichier ~actes #392  
- Le numéro des actes peut avoir un seul caractère. On se base sur le XSD @ctes ~actes #380
- Si une signature PADES est invalide, on l'indique désormais dans le message d'erreur ~actes #384
- On enregistre le mail original de la DGCL sur les retours prefecture (message 2-1, 3-1, 4-1, 5-1) ~actes #347
- On ne calcule plus le nombre de page de logs pour le super admin (trop gourmand)
- On peut récupérer la liste des documents d'un actes via l'API sauf si ceux-ci sont dans l'état 1, 2 ou 3 (was : 4 uniquement)
- Possibilité de filtrer les réponses d'hélios par collectivité pour les administrateurs de groupe #60
- Correction d'un bug empechant le filtrage des pes_aller par collectivité pour les administrateurs de groupe
- On ne se fait plus ejecter du traitement par lot si on ouvre une annexe qu'on ne rempli pas #203
- Dans la création d'une collectivité, on affiche juste les SIREN qui ne sont pas encore utilisé #362 pour le groupe selectionné #382
- Le type de la pièce principal n'était pas enregistré correctement ~actes
- Ajout de la typologie 99_SE **Fichier de signature électronique** quelque soit la matière et la nature de l'acte ~actes #393
- on vérifie que l'emetteur n'est pas vide ~actes #399
- on vérifier que le nombre d'annexe indiqué est identique au nombre trouvé ~actes #398  
- vérification des noms de fichiers attaché à un message métier ~actes #404 #405

### Ajouts

- Ajout des fonctions de l'API list_document_prefecture.php et document_prefecture_mark_as_read.php #379
- Ajout d'une page (caché) de pré-requis générique pour les navigateurs #360
- Ajout de la constante de configuration ACTES_DONT_VALID_SIGNING_CERTIFICATE permettant de ne jamais valider les certificat de signature des actes ~actes

## 3.0.14 - 2018-07-12

### Corrections

- Le mode document papier complémentaire ne retournait pas la bonne valeur. #386
- Lorsqu'un worker échoue, on ne sort plus du script
- Lorsque PADES-Valid envoi un code d'erreur, on passe le document en erreur

## 3.0.13 - 2018-06-28

### Corrections

- Lorsque le service pades-valid est down, on ne passe plus la transaction en erreur ~actes
- Le mode beanstalked ne prenait pas en compte les transactions en attente d'être posté.
- Correction d'un changement subtil dans l'api mailsec (detail-mail : renvoi 1/0 au lieu de t/f)

### Evolution

- actes-analyse-fichier-a-envoyer en mode beanstalked
- ajout des fonction de l'API : admin/services/add-service-user.php, admin/services/list-service.php, admin/users/add-user-to-service.php  #350

## 3.0.12 - 2018-06-05

### Corrections

- Erreur d'échapement provoquant le non fonctionnement de actes_transac_get_status.php

### Ajouts

- Ajout d'index pour la requete de récupération d'id à partir de l'uniqid

## 3.0.11 - 2018-06-04

### Évolutions

- le nombre de page des transactions helios n'est plus calculé pour le super admin (trop de ressources)
- Ajout du MODE_BEANSTALKD : test de la gestion de la tache actes-antivirus via un message broker

### Retrait

- retrait de la constante ANTIVIRUS_UPSTART_TOUCH_FILE devenue inutile
- retrait de la constante ANTIVIRUS_TMP_PATH devenue inutile

## 3.0.10 - 2018-05-24

### Corrections

- Sélection de la méthode login/mot de passe lors de la création d'un nouvel utilisateur à partir d'un utilisateur existant #290
- Correction d'un warning lorsqu'un certificat contenait un champs multivalue (par exemple l'OU) #340
- Ajout d'un message d'erreur lorsque l'on tente d'importer un certificat en masse et qu'il y a une erreur #270
- Suppression des boutons "Annuler", "Validé" et "Refusé" pour le super admin et l'admin de groupe qui ne pouvait de toute manière pas les utiliser #279
- Problème sur le filtre des mail sécurisé non-paginé #74
- les antislash n'était pas récupéré correctement dans certain cas
- possibilité de filtrer avec des caractère \_ #67
- Problème de sécurité (niveau moyen) sur l'utilisation de l'API de changement de status des PES Retour (pas de controle de droit) #161
- On ne peux plus valider plusieurs fois la même transaction #64
- Ajout d'un filtre SIREN sur la liste des collectivités #59
- Possibilité pour le super admin d'archiver les transactions en traitement par lot #154
- Recupération correcte de la date des courriers des préfectures, corrigeant le problème de mauvaise date dans les réponses #364 ~Actes

### Évolutions

- Le lien vers les logs est présent quelque soit le statut ~actes #281
- Le changement du nombre d'éléments affiché par page ramène à la page 1 #75
- La colonne fax est remplacé par la colonne SIREN sur la liste des collectivités
- Modification du message de suppression d'un lot quand il reste des fichiers #78
- Utilisation unique de PDO afin de diminuer le nombre de connexions simultané à la base #377
- Ajout de redis pour la gestion des sessions PHP (docker)

### Ajouts

- Méthode /api/info-connexion.php permettant de récupérer les informations sur l'utilisateur et la collectivité de la connexion #239
- Ajout du passage manuel en erreur dans le journal des évenements #81
- Ajout script rapport sur l'historique d'une collectivité

## 3.0.9 - 2018-05-04

### Corrections

- erreur SQL si la nature de l'acte n'est pas envoyé sous la forme d'un entier
- Suppression d'un warning dans la reception des mail sans attachemnt.
- Bloquage de la file actes en cas d'erreur trop longue ~actes #370 #369
- On accepte les pièces jointes en XML pour n'importe quel type d'actes et sans limitations (mode formulaire) ~actes #333

### Évolutions

- les messages d'erreurs de la mise en cloud actes passe de critical à error (trop de faux positifs)

### Retraits

- Suppression de helios_ftp_login et helios_ftp_password qui ne servent à rien (on prend les login/pass dans la macro) ~helios #159
- Suppression de ext_siret, inutile depuis le nouveau traitement des PES Retour ~helios

## 3.0.8 - 2018-04-18

**Cette version nécessite une modification de la base de données**

### Corrections

- backport d'un hotfix corrigeant des lancement d'erreurs inaproprié dans la sauvegarde cloud
- Correction d'un problème d'intégration d'actes arrivant en 0ko car l'analyse arrivent avant la fin du download du mail
- La DGCL envoi des fichiers métier et des enveloppes métier avec plus de 4 chiffres sur le compteur journalier

### Ajouts

- Ajout d'un index sha1 sur la table helios_transactions.
- HELIOS PES_RETOUR : création d'un rapport indiquant les SIRET appartenant à plusieurs collectivité

## 3.0.7 - 2018-04-10

### Corrections

- Bug sur le traitement par lot (problème de sécurité sur chrome sur le mime-type du fichier javascript)

## 3.0.6 - 2018-04-10

### Évolutions

- Journalisation de la suppression d'une transaction super admin ~actes ~helios #83
- Dans la création d'une collectivité, on affiche juste les SIREN qui ne sont pas encore utilisé #362
- Redémarrage systématique du tunnel ipsec s'il y en a un pour la communication avec Helios
- On ne vérifie pas les transferts SAE vieux de plus de 60 jours automatiquement
- Versement des flux PESv2 ayant plus de 15 jours
- Versements des actes uniquement ceux créés après le 01-06-2008

### Corrections

- Docker : oublie de la configuration de l'emplacement des résultats de requêtes dans le journal
- Certain PES apparaissait avec 0Ko, peut-etre car ils étaient analysés avant d'être entièrement récupérés ~Helios #341
- Ajout du HOMEDIR dans les script lancé par supervisord #327
- typo (mail et état de l'annulation acquitéE) #354
- actes_transac_show, entrée insuffisamment filtré provoquant des erreurs SQL (pas de faille) #314
- Suppression du nombre maximum de caractère dans la recherche pas NomFic #153
- Remplacement de Phar par tar pour la génération des notifications #361
- Ajout d'un message d'erreur si on tente de supprimer un groupe avec des utilisateurs dedans ~mailsec #62
- Problème lors de la connexion à Pastell si on ne récupère pas du JSON #365

### Ajouts

- Constante EMAIL_ADMIN_TECHNIQUE permettant d'envoyer les erreurs critique
- Surveillance des échecs de lancement des process par supervisord
- Ajout d'un lien vers la liste des utilisateurs de la collectivité sur la page d'une collectivité #349
- Affichage de l'intitulé de la classification dans le bordereau acquittement ~actes #42
- Ajout du script de rapport des versement des actes et helios

### Retrait

- supression de workspace/helios/response_backup lors de la création de la structure du workspace

## 3.0.5 - 2018-03-06

**Cette version nécessite une modification de la base de données**

### Corrections

- Docker : les certificats TIME_STAMP appartiennent à www-data
- Docker : corrections minimes de syntaxes dans le bootstrap (Pastell remplacé par S2LOW, Demou par Demos)
- Docker : le lien symbolique letsencrypt était écrit en dur sur s2low.test.libriciel.fr au lieu de prendre l'URL en paramètre
- Docker : la variable IMAP_LOGIN n'était pas prise en compte dans la génération du fichier DockerSettings.php

### Ajouts

- Docker : ajout d'un fichier compose par defaut
- Les URL de Libersign sont renseignées par défaut
- Utilisation d'un stockage objet pour les ~Actes #345
- Constante EMAIL_ADMIN_TECHNIQUE permettant d'envoyer les erreurs critique
- Surveillance des échecs de lancement des process par supervisord

### Évolutions

- Docker : la base de données de tests n'est créée uniquement lorsque la variable POSTGRES_DATABASE_TEST est présente
- Docker : contrôle d'accès à la base de données dans le entrypoint
- Docker : dans le bootstrap, on vérifie la présence d'un utilisateur avec le rôle SADM
- Docker : il n'y a plus qu'une seule variable d'environnement pour définir l'URL (sans le http(s))
- Log : ajout de Monolog pour la gestion des journaux. Ajout des constantes LOG_FILE et LOG_LEVEL
- Docker : le fichier de config.php est adapté à l'utilisation par défaut dans un Docker
- Ménage dans le fichier config.php
- Docker : le fichier de config.php est adapté à l'utilisation par défaut dans un Docker
- Docker : docker-compose.defaut.yml passe en version 3.5
- Docker : utilisation des network, de la notion dependance et des volumes par défaut

### Retrait

- Docker : suppression des paquets pdfsam et pdftk
- Suppression du fichier LocalSettings.php devenu inutile

## 3.0.4 - 2018-02-21

### Corrections

- Correction d'un problème de vérification du certificat dans PADES valid.
- les & ne passait pas dans le nom des utilisateurs lors de la génération d'un acte ~Actes #346
- rétablissement de l'export des fichiers OCRE dans le docker
- Problème majeur en cas de bug sur l'antivirus : toutes les transactions passe à erreur : on préfère donc tuer le script ~Helios
- Correction d'une erreur de syntaxe dans la requete SQL dans le script archive_all_transaction.php pour les actes
- Correction dans la requete SQL dans le script archive_all_transaction.php pour les actes sur le délais avant versement
- Harmonisation des noms des programmes dans supervisord
- Certain chemin d'execution laissait des fichier s2low_xades dans /tmp/ ~Helios #348
- Certain PES apparaissait avec 0Ko, peut-etre car ils étaient analysé avant d'être entièrement récupéré ~Helios #341 (correction invalide, reprise de la correction en version 3.0.6)
- Correction d'un problème de sécurité

### Ajouts

- script de monitoring du service pdf-stamp
- script permettant de supprimer les fichiers PES qui ne se trouvent pas dans la base de données ~Helios #343
- script permettant la validation d'une archive actes ~Actes
- script de monitoring des notifications des actes

### Évolutions

- préparation de s2low pour le versement des @ctes dans un stockage objet
- un utilisateur peut a nouveau "refusé" une transaction "en attente d'être signé" sur la liste des transactions

## 3.0.3

### Évolutions

- Modification de la position du tampon ~Actes

## 3.0.2

### Évolutions

- Modification de l'opacité du tampon (0.5 -> 0.8) ~Actes #339
- Ajout de log dans le tamponnage ~Actes

### Corrections

- Typo actes-reception-fichier (Début -> Fin) ~Actes #338
- Reception des actes ; Expunge de la boite à la fin du processus pour éviter les locks ~Actes #337
- Correction d'une anomalie majeure sur la validation de la signature PADES (impossible de valider plusieurs actes sur le même appel) ~Actes #331
- Correction d'une anomalie majeure : on analyse que la première transaction des enveloppes ~Actes #336
- Les enveloppes d'anomalie ne sont jamais supprimé ~Actes #336
- Typo dans le nom d'un script supervisor ~Actes #328
- La signature par lot dans @ctes n'était plus fonctionnel ~Actes #334
- Modification script supervisor pour éviter les warning #327
- Correction d'un problème dans le tamponnage des actes sur les notifications ~Actes

## 3.0.1 - 2017-12-26

### Corrections

- problème de nommage de fichier avec pas assez d'entropie et pouvant envoyer de mauvaise information lors de la création des PES via l'APIs
- Augmentation de la longueur du type de fichier inclu dans actes de 64 à 512 car les réponses de la préfecture peuvent inclure des fichier avec des types très long #320 ~Actes
- vérification de la balise de scellement sur les annexes (tdt-lib-actes) #319 ~Actes
- problème si les fichiers recu sur Helios sont plusieurs fois en erreur #321 ~Helios
- correction d'un problème sur l'api admin_user_edit_handler #317  
- nettoyage de fichier temporaire (analyse fihcier actes)

## 3.0.0 - 2017-12-15

### Corrections

- suppression de la memory_limit sur jour.php
- optimisation script grand-ménage

## 3.0.0-rc4 - 2017-11-24

### Corrections

- une erreur SQL apparaissait quand on tentais de supprimer un utilisateur de l'annuaire et que celui-ci
    était encore dans un groupe #295
- Le typage disparraissait après avoir posté un actes provoquant une erreur (doublon) #296
- Connexion impossible après modification en masse de certificat partagé #293
- Ajout des type possible en fonction de la matiere2 0 dans le code ~Actes #292
- Typage par défaut des pièces ~Actes #298
- Suppression d'un warning lorsqu'on clique sur suppression d'un utilisateur d'un service sans avoir choisi l'utilisateur #301
- Ajout de libersign v1 dans le docker (rétro-compatibilité IE11)  
- La fenêtre de classification reste ouverte sous IE 11 #299
- Typo #257
- Suppression de la possibilité de valider une transaction avant le délai de 2 mois par lot #303  
- Suppression de la possibilité de valider une transaction dans l'état 'en attente de signature' #307
- Suppression de warning (mail récapitulatif helios en erreur)

### Évolutions

- Vérification du content-type du fichier contenant les SIREN #265
- Les scripts géré par supervisord peuvent être tuer (SIGTERM) de manière élégante #302
- Mise à jour du schéma XSD des PES en version 5.5 (applicable dès fin novembre 2017)
- Console admin Helios: ajout d'infos dans le mail des transactions à l'état transmis ~Helios #306

### Retrait

- Suppression de la possibilité de soumettre des signatures sur la console (cohérence avec l'envoi d'actes) #183  

## 3.0.0-rc3 - 2017-11-15

### Évolutions

- un warning apparait 90 jours avant l'expiration d'un certificat (was: 30 jours)
- Libellé des champs certificat plus explicite (on précise qu'il s'agit de la partie publique)
- Normalisation des noms des scripts cron (actes-*, helios-*,...)
- Utilisation de supervisord en remplacement de upstart
- Ajout du typage du fichier principal de l'actes (mauvaise interpretation initiale de l'exigence) ~Actes #291
- Restriction des types de pièces en fonction de la classification (mauvaise interprétation de la notice) ~Actes #292

### Corrections

- suppression d'un lien mort ~Actes #273
- possibilité de désactiver un utilisateur #271
- controle minimum du SIREN même si VERIFICATION_SIREN est désactivé #266
- VERIFICATION_SIREN est maintenant true par défaut
- Correction du problème du retour de la fonction actes_transac_get_files_list.php ne retournant pas les fichiers avec
    des caractères accentués ~Actes #278
- Correction du titre en cas de création d'un nouvel utilisateur partageant un certificat #289
- On accepte que des documents PDF pour la convention ~Actes #287
- Le filtre helios sur la date d'acquittement ne fonctionnait plus depuis que les fichiers étaient en information disponible ~Helios #283
- L'import des SIREN ne fonctionnait qu'avec des fichiers Unix, ajout du support Windows et Mac OS #265

### Élements dépréciés

- VERIFICATION_SIREN est déprécié et sera supprimé dans une prochaine version mineure du produit

## 3.0.0-rc2

### Corrections

- Liste des PES Retour, filtre par défaut à *tous les états* à la place de *non lu*. ~Helios #253
- Typo #241
- Modification libéllé réponse au message ministère ~Actes #194
- Seul l'admin de groupe et le super admin peuvent uploader une convention @ctes ~Actes #147
- Correction d'un warning lors du post d'un acte hors 7-1 avec un fichier XML et une annexe XML ~Actes #251
- Typo #250, #247, #255, #246, #245, #242, #248
- MIOCT est remplacé par la constante ACTES_MINISTERE_ACRONYME

## 3.0.0-rc1 - 2017-09-18

### Ajouts

- Création du script cron/actes-analyse-fichier-a-envoyer.php qui vise à remplacer la partie Tomcat #190 ~Actes
- Création du script cron/acte-envoi-fichier.php pour remplacer la partie Tomcat #192 ~Actes
- Création du script cron/acte-reception-fichier.php pour remplacer la partie Tomcat #195 ~Actes
- Création du script cron/acte-analyse-ficier-recu.php pour remplacer la partie Tomcat #197 ~Actes
- Visualisation rapide de l'état des transactions Actes #90 ~Actes
- Le super admin peut envoyer des demandes de classification via la console (sur la page de modification d'une collectivité) ~Administration ~Actes
- Le super admin peut envoyer une demande de classification forcée à vide (pas de date de classification) ~Administration ~Actes
- Exporter les informations sur les Actes au format *FONCT-05* CSV #180 ~Actes ~ACTES2.2
- Les transactions de type envoi de la préfecture sont affichés pour tout le monde  #223 ~Actes
- le fichier contenant l'AR Actes est maintenant inclu systématiquement dans les notification d'aquittement  ~Actes
- Notification des AR d'envoies de piece complémentaire et de réponse à des lettre d'observation #224 ~Actes
- Notification généralisé des transmissions reçu depuis @ctes. #181 ~Actes
- Horodatage systématique des mails de notification  #181 ~Actes
- Constante OPERATEUR_DE_TELETRANSMISSION permettant de saisir le nom utilisé par exemple dans les bordereau d'acquittement #225
- Ajout d'une infobulle pour indiquer que le certificat de l'utilisateur connecté expire bientôt #93
- Ajout de cette information dans la liste des utilisateurs (certificat expiré ou dans moins de 30 jours) #93
- Validation de la signature PADES des ~Actes #176
- Possibilité d'ajouter la conventions ~Actes lors de l'édition d'une collectivité #147

### Corrections de sécurités

- Correction d'une faille de sécurité sur les modules ~Administration

### Évolutions

- La validation de la signature des PES Aller n'affiche plus une erreur si le fichier n'est pas signé #188 ~Administration
- Les admin sont autorisés à récupérer les PES Retour via l'API #164 ~Helios
- modification du libellé des natures d'actes dans le filtre de recherche #163 ~Actes
- Sur la visualtion d'une transaction, le lien de téléchargement des archives transmisses passent du titre (Fichiers contenus dans l'archive transmise) en bas
du tableau des fichiers contenus dans l'archive ~Actes
- Il est maintenant possible de télécharger l'archive transmisse dans le cas des messages 7-1 (demande de classification) ~Actes
- Les messages de classification passent en acquittement recu. Ce mécanisme n'est fiable que dans les cas où il n'y a qu'une enveloppe en cours. ~Actes
- Il est possible de récupérer le fichier de classification directement sur la console ~Actes ~Administration
- Par défaut, le filtre de la liste des actes est mis à "tous les états" à la place de "en cours" #101 ~Actes
- La fonction de l'API helios_transac_get_status.php complete le champs message afin d'y mettre le message d'erreur
ou le message de passage dans l'état #110 ~Helios  
- La liste des utilisateurs partageant un même certificat n'est plus sur la même page que le formulaire de modification
d'un utilisateur. L'affichage de cette nouvelle page a été optimisé. #18 ~Administration
- Le message d'horodatage est aggrémenter des champs présent dans l'exigence *ARCH-01* #21 ~Actes ~ACTES2.2
- Le nom du fichier contenant l'AR Actes (message 2-1) envoyé dans les notification s'appelle
`<num_unique_acte>-<type transaction>-<identifiant s2low>-reponse.xml` à la place de retour.xml ~Actes
- Mise en place du nouveau schéma Actes # 2.2 #177 ~Actes
- Gestion du multi-canal : transmission complémentaire au format papier (ajout d'une case à cocher dans le formulaire de création d'un actes) #184 ~Actes
- Gestion multi-canal : information reprise dans la description de l'acte et dans le bordereau d'acquittement #184 ~Actes
- Gestion de la typologie des pièces jointes #179 ~Actes
- Modification en masse des certificats partagés #31 ~Administration
- Limitation de la taille des pièces jointes dans les mails sortants à 10Mo
- Possibilité d'envoyer des réponses (flux 3 et 4) en JPG et PNG (en plus de XML et PDF). #194 ~Actes

### Corrections

- Correction d'une lettre f qui apparaissait sur l'édition d'une collectivité #187 ~Administration
- Le status de l'archivage des actes est considéré à tort comme refusé pour les bordereau SEDA 1.0 #146 ~Actes  
- La date d'acquittement du tampon est prise dans l'ARActe #173 ~Actes
- Mise à jour du libellé du status 11 - Aquittement de document reçu -> Acquittement de document reçu #158 ~Actes
- Bug sur l'attribution des PES Acquit (mode NomFic non unique) #196 ~Helios
- Correction de l'orthographe des notes de mise à jour de version #89
- Possibilité d'accéder à un message d'urgence dont le titre est vide #200 ~Administration
- La tentative de création d'un groupe vide ne provoque plus d'erreur #198 ~Mailsec
- Ne pas pouvoir traiter les fichier d'un lot déjà traité #201 ~Actes
- Les jetons d'horodatage utilise le format ISO 8601 pour le message horodaté
- Remplacement complet du système de tampon des actes #202 ~Actes
- Correction d'un problème d'encodage dans le traitement par lot #211 ~Actes

### Retraits

- L'ancienne bannière et la constante NEW_BANNER remplacée définitivement par la nouvelle bannière
- Suppression du check de l'archive lors de son postage (check fait à l'étape de validation) ~Actes
- Suppression de la constante ACTES_CHECK_ARCHIVE_SERVLET ~Actes
- Suppression de la constante ACTES_ANTIVIRUS_COMMAND, utilisation de ANTIVIRUS_COMMAND à la place #220

## 2.6.6 - 2017-10-02

### Corrections

- Prise en compte des cas où les PES_ALLER ne sont plus sur le serveur

## 2.6.5 - 2017-06-29

### Ajouts

- Utilisation d'un stockage objet pour les PES Aller

### Corrections

- Correction d'un bug provoquant une erreur si deux mails sécurisés étaient envoyés à la même seconde. ~mailsec
- Suppression de l'autocomplete sur les mots de passe de la création d'utilisateur (bug Firefox)
- Suppression d'un bug potentiel dans la liste des actes sur les actes à l'état 21 (document recu (pas d'AR)) ~Actes
- Message d'erreur sur un upload de fichier PES Aller qui se serait mal passer ou qui est vide ~Helios
- Correction de fautes d'orthographes

## 2.6.4 - 2017-06-14

### Corrections

- Correction d'un bug d'affichage empechant de répondre au message de type courrier simple ~Actes

## 2.6.3 - 2017-06-09

### Évolutions

- Mise à jour du schéma PES en version 5.3 ~Helios

## 2.6.2 - 2017-06-08

### Ajouts

- script de monitoring des fichiers Actes et Helios restés trop longtemps dans un certain état ~Actes ~Helios

### Evolutions

- création d'un pipeline d'intégration continue
- les fichiers version.txt et revision.txt sont remplacé par manifest.txt géré automatiquement par gitlab
- la nouvelle bannière introduite en version 2.5.0 devient la bannière par défaut
- modification du système de mise à jour de la base de données.

### Corrections

- correction de warning dans le code

## 2.6.1 - 2017-05-23

### Ajouts

- le super-admin peut ajouter et publier un message d'information sur le back-office visible pour tous les utilisateurs ~Administration

### Evolutions

- migration du code source vers git

## 2.6.0 - 2017-04-13

### Evolutions

- Vérification de l'unicité des PES ALLER, la vérification de l'unicité des PES ALLER se fait désormais sur
(NomFic, CodCol) et plus uniquemnet sur (NomFic). ~Helios
- Le script de migration (script/migration/# 2.5-to-# 2.6.php) permet  de mettre à jour la base de données.

## 2.6.3 - 2017-06-09

- Mise à jour du schéma PES en version 5.3.

## 2.6.2 - 2017-06-08

- Version n'apportant pas d'évolution fonctionnelle.

## 2.6.1 - 2017-05-23

- Administration : permettre l'ajout d'un message d'information pour les utilisateurs ;

## 2.6.0 - 2016-10-27

- Module ACTES : ajout d'une API pour déclencher la télétransmission d'un acte via RGS** partagé avec un couple identifiant/mot de passe ;
- Module ACTES : une date doit toujours être indiquée dans le demande de classification ;
- Administration : affichage de l'identifiant de l'utilisateur dans la partie "Gestion des utilisateurs".

## 2.5.1 - 2016-10-27

- Administration : ajout du département de la Mayotte.

## 2.5.0 - 2016-10-01

- Modules ACTES : prise en compte des nouveaux mails d'acquittement du MI ;
- Modules ACTES : possibilité d'ajouter la date d'affichage dans le tampon apposé par S²LOW ;
- Modules ACTES : le système de notification par défaut, introduit en version 1.3.2, est désormais le seul utilisable ;
- Modules ACTES : enregistrement de l'intitulé de la classification au moment de la création de la transaction ;
- Modules ACTES : API : en cas d'erreur le retour -1 est complété par la raison de l'erreur ;
- Modules HELIOS : contrôle du bon paramétrage du module pour autoriser à télétransmettre ;
- Administration : optimisation de l'accès au journal des événements ;
- Administration : ajout d'une page d'administration des transactions HELIOS ;
- Administration : contrôle renforcé sur les adresses mails.

## 2.4.1 - 2016-05-24

- Modules HELIOS : correction sur certains fichiers acquitement rejetés à tort ;
- Modules HELIOS : surveillance des fichiers d'acquittement non-intégrés et acquittement non-reçus ;
- Modules HELIOS : correction liste sur FTP différent en fonction du serveur.

## 2.4.0 - 2016-05-16

- Module ACTES/HELIOS : système de versement global des transactions sur une période donnée par collectivité ;
- Module ACTES : partie JAVA - interdiction des numéros de moins de deux caractères ;
- Module ACTES: correction de l'expression régulière chargée de vérifier les numéros d actes ;
- Module ACTES : interdiction de finir un numéro d actes autrement que par 0-9A-Z ;
- Module HELIOS : amélioration du système de canonisation des fichiers XML ;
- Module ACTES/HELIOS : système de versement passé en mode asynchrone ;
- Administration : ajout de l'API permettant de tester si le certificat d'authentifiation est de niveau RGS ;
- Récupération de fichier OCRE (orchestrateur uniquement) ;
- Optimisation de la consultation du journal des événements.

## 2.3.2 - 2016-04-18

- HELIOS : Modification du schéma XSD : passage à la version 491.

## 2.3.1 - 2016-01-27

- HELIOS : page de validation des PES_ALLER pour visualiser les éventuelles erreurs ;
- HELIOS : on ne vérifie plus les schémas XSD ;
- ADMINISTRATION : amélioration technique du processus de vérification des certificats lors de l'authentification ;
- HELIOS : améloriation du script chargé de récupérer les SIRET des PES_ALLER.

## 2.3 - 2015-12-05

- Ensemble des pages : les champs recherche sur les collectivités ont été changés en select2 ;
- Optimisation du journal des événements ;
- Administration : les personnes partageant un certificat sont affichées par ordre alphabétique ;
- Administration : amélioration du filtre de recherche sur la collectivité ;
- Administration : dans la fiche utilisateur on informe si la partie publique du certificat est issue d'une AC accréditée ;
- La liste des modules est fixe dans les menus ;
- Nouveau mécanisme de contrôle des certificats basé sur l'empreinte SHA1 ;
- Module ACTES/HELIOS : on ne peut plus télétransmettre si le certificat n'est pas un RGS ;
- Module ACTES : authentification par RGS* associée à un RGS** ;
- Module ACTES : nouvelle présentation du système d'authentification dans la fiche utilisateur ;
- Module ACTES : possibilité de verser par lot les actes à l'état validé ;
- Module ACTES : le trigramme et le quadrigramme sont désormais vérifiés par la partie PHP ;
- Module ACTES : l'administrateur de groupe ne voit plus les boutons Créer, Importer et Traitement par lot ;
- Module ACTES/HELIOS : S²LOW ordonne à Pastell de supprimer le document lorsque le versement s'est correctement déroulé ;
- Module HELIOS : ajout de la recherche sur la balise NomFic ;
- Module HELIOS : Notification aux administrateurs de la plate-forme lors d'un problème d'accès au FTP de la DGFiP ;
- Module HELIOS : Déplacement du fichier PES dans un dossier spécifique en cas d'erreur sur le fichier ;
- Module HELIOS : retour du bouton pour le versement par lot ;
- Module HELIOS : nouveau système de collecte des SIRET pour l'analyse des PES_RETOUR ;
- Module HELIOS : permettre de sélectionner plusieurs transactions ;
- Module HELIOS : les flux PES_ALLER sont signés techniquement par S²LOW, dans certains cas ;
- Module ACTES/HELIOS : changement d'id de transfert par identifiant Pastell ;
- Module HELIOS : l'analyse des fichiers par l'antivirus se fait, désormais, lors de l'analyse du PES ;
- Module HELIOS : versement des flux à l'état Refusé par le SAE ;
- Module ACTES : correction bug : il n'est plus possible de verser deux fois le même ACTES ;
- Module ACTES : correction bug : il n'est plus possible de créer un acte sans fournir le pdf principal ;
- Module ACTES : correction bug : création d'une transaction sans indiquer la nature ;
- Module HELIOS : correction bug : analyse des PES_ALLER lorsque la balise NomFic contient des accents ;
- Module Mails : correction bug ; sous IE, il n'était pas possible de saisir deux emails dans le même champ;

## 2.2 - 2015-09-25

- API : une nouvelle API permet de tester la connexion à la plate-forme S²LOW ;
- Administration : l'authentification par certificat RGS* peut être couplée avec un RGS** ;
- Administration : il ne peut plus y avoir d'espace dans l'adresse mail d'un utilisateur ;
- Module HELIOS : le système d'analyse et de télétransmission des flux PES a été scindé en deux parties ;
- Module HELIOS : les schémas PESv2 ont été mis à jour et récupéré sur Xemelios ;
- Modules ACTES et HELIOS : le menu de sélection de la collectivité a été remplacé pour etre plus intuitif ;
- Module HELIOS : les PES récupérés erronés sont déplacés dans un dossier spécifique ;
- Module ACTES : mise en place de la macro ACTES_MAIL_BACKUP ;
- Module ACTES : correction bug : si l'antivirus n'était pas lancé, les flux ne pouvaient pas être analysés et ils passaient en erreur ;

## 2.1.01 - 2015-06-23

- Module HELIOS : correction bug : la signature HELIOS introduisait un ID dans les bordereaux ;
- Module ACTES : correction bug : les signatures des transactions n'étaient plus incluses dans le cadre de la soumission d'une enveloppe complète ;
- Module ACTES : correction bug : l'administrateur de collectivité ne pouvait pas modifier les paramètres de sa collectivité ;
- Administration : correction bug : affichage de la date expiration du certificat ;
- Module HELIOS : ajout de la macro HELIOS_FTP_PASSIVE_MODE ;
- Module HELIOS : pour la signature en local, les PES sont signés au niveau bordereaux si ils ont des ID ;

## 2.1 - 2015-06-08

- Module HELIOS : correction bug : erreur d'import des fichiers dont les noms comportaient des caractères spéciaux ;
- Module ACTES : correction bug : horodatage du nom de la personne déclenchant la télétransmission ;
- Module ACTES : correction bug : l'administrateur de collectivité ne pouvait pas modifier les paramètres de sa collectivité ;
- Module HELIOS : changement du message pour l'état Posté ;
- Module ACTES : optimisation du mécanisme de vérification des fichiers par l'antivirus ;
- Optimisation de la base de données ;

## 2.0 - 2015-05-05

- Module HELIOS : prise en charge des XML complexes pour la signature ;
- Module ACTES : correction bug : vérification que le fichier PDFTK existe ;
- Module ACTES : correction bug : amélioration vérification des signatures lors de l'import des enveloppes ;
- Module ACTES : correction bug : remise en place du versement par lot ;
- Administration : correction bug : vérification du département et de l'arrodissement via les API ;
- Administration : correction bug : seul le superadmin peut modifier le paramétrage SAE ;
- Module ACTES : traitement par lot changement du bouton Envoyer par Créer le lot ;
- Module ACTES : ajout de l'identifiant unique dans le tampon ajouté par S²LOW ;
- Module ACTES : transmission de la signature électronique de l'acte lors du versement au SAE ;
- Module ACTES : permettre à un administrateur de collectivité de verser au SAE ;
- Module ACTES : transmission de l'acte tamponné lors du versement au SAE ;
- Module ACTES : nouvelle API permettant de récupérer la liste des documents d'une transaction ;
- Passage sous Postgres 9.4 ;
- Passage sous Openssl 1.0 ;
- Passage sous PHP5.5 ;

## 15.01 2014-12-17

- Module MAILS : correction bug : si un destinataire est en double dans un même champ on obtient une page blanche ;
- Module MAILS : correction bug : lors de l'ajout d'un contact, le champ description n'était pas pris en compte ;
- Module MAILS : correction bug : le champ CCI n'était pas autocomplété ;
- Module HELIOS : correction bug : les commandes SITE n'étaient pas correctement envoyées ;
- Correction bug : suppression des \n dans les boites de dialogues ;
- Correction de fautes d'orthographe ;
- Correction bug : prise en compte du droit choississez dans l'affichage des modules autorisés ;
- Module ACTES : agrémentation des informations envoyées à Pastell dans le cadre du versement SEDA ;
- Module ACTES : ajout de l'identifiant unique dans le tampon ;
- Module HELIOS : sécurisation de l'API de mise à disposition des PES_ACQUIT/ACK/NACK ;
- Module HELIOS : amélioration de la regexp d'analyse des retours des commandes FTP ;
- Module HELIOS : ajout de la vérification de la taille du PES lors de l'import via API plus contrôle par l'antivirus avant d'accepter le dépot ;
- Module ACTES : nouvelle API permettant la télétransmission en préfecture via redirection d'URL ;
- Module MAILS : les commandes console sont commentées pour garder une compatibilité avec IE ;
- Mise en place d'un fichier de configuration générique ;

## 15 - 2014-08-05

- Refonte globale de l'interface web pour être aux normes d'accessibilités ;
- Passage sous Bootstrap v3 de l'interface web ;
- Module ACTES : ajout de la signature électronique de l'acte (au format PDF) ;
- Module ACTES : possibilité d'envoyer un PDF joint à un XML (acte budgétaire) ;
- Module ACTES : lors d'une annulation,l'acte principal passe à l'état annulé et la transaction d'annulation passe à l'état acquitement reçu ;
- Module HELIOS : optimisation du module ;
- Module HELIOS : refonte du système d'envoi des flux PES pour passer de JAVA à PHP ;
- Module HELIOS : ajout de la signature électronique du flux PES_ALLER;

## 14.01 - 2014-01-14

- Module ACTES : correction bug : versement SEDA via Pastell en HTTPS;

## 14 - 2013-11-09

- Module MAIL : correction bug : problème d'encodage des API ;
- Administration : versement au SAE via Pastell;
- Module ACTES : versement par lot au SAE ;
- Module ACTES : ajout du nouvel état d'attente via les API pour que les actes puissent être validés par l'agent télétransmetteur ;
- Module ACTES : ajout d'un statut permettant de temporiser l'envoi d'un acte après sont dépôt sur le TdT;
- Module HELIOS : versement au SAE ;
- Module DIA : ajout du module ;

## 13.2 - 2013-05-06

- Module ACTES : nouveau système de notification d'acquittement pour les agents télétransmetteur

## 13.1 - 2013-01-15

- Module ACTES : correction bug : la variable pdfgenerate n'était pas correctement réinitialisée. Cela entrainait une erreur sur le PDF joint aux notifications automatiques ;
- Module ACTES : correction bug : il est possible de filtrer les actes sur l'état "Refus d'envoi" ;
- Module ACTES : correction bug : l'état "en cours" prend en compte les transactions aux états "Document reçu" et "Acquittement envoyé";
- Module HELIOS : correction bug : l'émission des flux PESv2 est beaucoup plus rapide et la servlet ne se bloque plus ;
- Module HELIOS : correction bug : les administrateurs de groupes ne peuvent plus lister les transactions des collectivités n'appartenant pas à leur groupe ;
- Module HELIOS : correction bug : la gestion des droits sur ce module a été revue pour ne plus dépendre du module ACTES ;
- Module MAIL: correction bug : protection renforcée sur l'insertion de code dans les champs Nom et adresse mail ;
- Module ACTES : utilisation du logiciel pdfsam-console pour rendre les actes au format PDF tamponnables lorsque pdftk ne peut être utilisé ;
- Module HELIOS : amélioration de la récupération des PES ACK/NACK en utilisant un script PHP en lieu et place d'une servlet JAVA car cette dernière se bloquait ;
- Module MAIL : le navigateur web Mozilla Firefox ne remplit plus automatiquement le formulaire avec les login/mot de passe de l'agent ;
- Administration : modification de l'API "Liste des collectivités". Il est possible de filtrer sur tout ou partie du SIREN ;

## 13 - 2012-10-31

- Module ACTES : correction bug : les PDFs optimisés sont pris en compte pour apposer le cartouche/tampon dans les mails de notifications ;
- Module ACTES : correction bug : la supression des dossiers temporaires unzip doit être faite après avoir changé de répertoire courant pour éviter un NOTICE ;
- Module ACTES : correction bug : les fichiers avec l'extension .PDF sont tamponnés ;
- Module ACTES : correction bug : la position du cartouche est fixe sur les documents ;
- Module ACTES : correction bug : l'orthographe du mot envoi a été corrigée sur différentes pages ;
- Module ACTES : correction bug : le caractère : est en trop sur certaines pages ;
- Module HELIOS : correction bug : la colonne suivi indique toujours le même nom ;
- Module ACTES : le versement au SAE intègre les courriers Ministèriel lié à l'acte versé ;
- Module ACTES : Le traitement par lot a été totalement revu et abandonne JAVA ;
- Module ACTES : Les mails de notification de reception d'un courrier Ministèriel sont plus explicites ;
- Module ACTES : Dans les détails d'une transaction, l'identifiant de transafert au SAE est indiqué ;
- Module ACTES : correction bug : la validation ou le refus d'un acte n'est plus possible si une demande d'annulation est en cours;
- Module HELIOS : Les API "graphique" ne sont plus présentes dans la page d'import ;
- Module MAIL : Le corps du mail de notification envoyé aux destinataires a été reformulé suite aux demandes des collectivités ;
- Module MAIL : Le nombre de carcatères autorisés dans le carnet d adresses pour les noms des contacts est passé à 100 ;
- Module MAIL : Les champs "Nom" et "Adresse mail" sont protégés contre l'insertion de code ;
- Admnistration : Le nombre de caractères autorisés dans le champ adresse électronique de diffusion d information est passé à 2000 ;
- Administration : Modification de l'interface de paramétrage du connecteur SAE ;
- Administration : De nouvelles API permettent la gestion des collectivités, des utilisateurs, des groupes ;

## 12.2 - 2012-06-01

- Module ACTES : les PDFs optimisés sont pris en comptes pour apposer le cartouche/tampon
- Module ACTES : les adresses mails par défaut ne sont plus décochables
- Module ACTES : le bouton versement SEDA s'affiche uniquement lorsque la collectivité est paramétrée
- Module ACTES : augmentation du niveau de logs pour les mails de notification envoyés automatiquement
- Module MAIL : la limite du nombre de caractères pour les adresses mails du carnet d'adresses est passée de 50 à 100.
- Module ACTES : correction bug : la suppression des fichiers temporaires entrainait un warning dans les logs
- Module ACTES : correction bug : lors de la réception d'un courrier Ministèriel, celui est désormais rattaché au propriétaire de l'acte concerné
- Module MAIL : correction bug : les sujets des mails dépassant 74 caractères subissaient un problème d'encodage
- Module HELIOS : correction bug : le mot list est remplacé par liste

## 12.1 - 2012-03-20

- Module ACTES et MAIL : correction bug : modification des entêtes des mails envoyés pour ne plus avoir de BAD HEADER
- Module HELIOS : correction bug : en cas d erreur lors de la transmis d un flux, un message indiquant le problème est fourni
- Module MAIL : correction bug : modification du code HTML pour ne plus être détecté à tort comme du SPAM
- Administration : correction bug : les siren sont vérifiés lorsqu ils sont ajoutés via le formulaire
- Administration : correction bug : problème d authentification avec des certificats dont les noms des AC comportent des accents
- Module ACTES : prise en compte des retours du MIOCT dont la partie numéro dépasse 4 caractères
- Module ACTES : ajout du statut classification mise à jour
- Module ACTES : suppression des dossiers et fichiers temporaires
- Module ACTES : les administrateurs de collectivités peuvent visualiser le détail des transactions
- Module HELIOS : prise en compte des nouveaux PES ACK délivrés par le DGFiP
- Module HELIOS : nouvelle méthode de récupération des flux mis à disposition par la DGFiP
- Module HELIOS : prise en compte des fichiers vide présents sur les serveurs de la DGFiP
- Module HELIOS : l administrateur de groupe peut lister les transactions par collectivité
- Administration : modification des intitulés des champs SAE dans les paramètres des collectivités

## 12 - 2011-11-16

- Module ACTES : Correction bug : Informations complémentaires sur les réponses aux flux 3 et4
- Module ACTES : Correction bug : Notifications à ne pas envoyer aux utilisateurs désactivés
- Module ACTES : Correction bug : Notification automatiques bloquées
- Module ACTES : Correction bug : Messages d erreurs eronnés lors de la création des actes
- Module ACTES : Correction bug : Mails de notifications envoyés en double
- Module ACTES : Correction bug : Les caractères Microsoft Word sont acceptés
- Module ACTES : Correction bug : La limite du nombre d annexes dans les transactions est augmentée et peut être modifiée simplement
- Module ACTES : Correction bug : L agent télétransmetteur est desormais notifié automatiquement
- Module ACTES : Correction bug : Tous les caractères sont acceptés dans l objet
- Module ACTES : Correction bug : La limite du nombre de fichiers dans le traitement par lot est augmentée et peut être modifiée simplement
- Module ACTES : Correction bug : Dans le tampon l orthographe a été corrigée
- Module MAIL : Correction bug : Les destinataires sont affichés par ordre alphabétique pour comparer plusieurs messages sur les mêmes listes
- Module MAIL : Correction bug : Saisie des adresses avec la souris sur Microsoft Internet Explorer
- Module MAIL : Correction bug : La macro TEXT est utilisée
- Administration : Correction bug : Login unique pour l ensemble de la plateforme
- Administration : Correction bug : Les certificats avec accents ne pouvaient pas être utilisés avec un login
- Module ACTES : Intégration des ACTES BUDGETAIRES
- Module ACTES : Connexion avec le SAE AS@LAE
- Module ACTES : Création d une API pour récupérer l ACTES avec le tampon
- Module ACTES : Amélioration des requêtes SQL
- Module ACTES : Le nom de la collectivité est indiqué dans les mails de notifications
- Module ACTES : Il est possible d effectuer des recherches sur l objet
- Module HELIOS: La collectivité émétrice est indiquée dans le détail de la transaction et dans la liste des transactions
- Module HELIOS: Le propriétaire de la transaction est indiqué dans le détail de celle-ci et dans la liste des transactions
- Module MAIL : Il est possible de modifier un contact
- Module MAIL : Le texte du mail reçu a été modifié pour ne plus être équivoque
- Module MAIL : Via les API il est possible d envoyer un mail sécurisé sans le/les destinataires soient présents dans le carnet d adresses
- Administration: La date d expiration du certificat de l utilisateur est indiquée
- Administration: Les SIREN sont affichés par ordre croissant
- Administration: Il possible d ajouter directement un SIREN dans un groupe
- Administration: Les utilisateurs sont triés par ordre alphabétiques

## 11 - 2012-11-16

- Module ACTES : Intégration des flux ACTES 1.4
- Module ACTES : Traitement par lot : les actes peuvent se situer dans des dossiers différents
- Module ACTES : Les actes sont disponibles avec tampon indiquant la date d'envoi à la prefecture et de réception par celle-ci
- Module ACTES : Le mail de notification d'accusé de réception inclu le bordereau d'acquitement
- Module ACTES : La classification est mise à jour automatiquement
- Module ACTES : Intégration des groupes/services
- Module ACTES : Le bouton valider apparait au bout de 2 mois après l'acquitement
- Module ACTES : Les recherches ne sont plus sensibles à la casse
- Module ACTES : Les collectivités et groupes sont listés par ordre alphabétique
- Module ACTES : Un même certificat peut être utilisé par plusieurs utilisateurs via un login/mot de passe
- Module ACTES : Le descriptif du certificat apparait dans la fiche de l'utilisateur
- Module ACTES : Les dates de décisions ne peuvent plus être dans le futur
- Correction bug : les espaces entre les destinataires ne sont plus supprimés
- Module MAIL : Correction bug : les espaces dans les noms des fichiers ne sont plus tronqués
- Module MAIL : Correction bug : le nombre de destinataire n'est plus limité
- Module MAIL : Les statuts des messages sont plus détaillés
- Module MAIL : Chaque élément récupéré par un destinataire est horodaté
- Module MAIL : Le jour et l'heure où un destinataire a pris connaissance du message sont indiqués et horodatés
- Module MAIL : Nouvelle présentation des mails reçus
- Module MAIL : La gestion du carnet d'adresses a été complètement revue.
- Module MAIL : Il est possible de créer des contacts et de les placer dans un ou plusieurs groupes
- Module MAIL : Import d'un carnet d'adresses
- Module MAIL : Le mot de passe n'est plus indiqué par défaut dans le mail de notification
- Module MAIL : Il est possible de stipuler l'adresse mail émettrice pour l'ensemble des utilisateurs de la collectivité
- Module MAIL : Dans le sujet des mails expédiés apparaît entre crochets le nom de la collectivité
- Module MAIL : Suppression des menus déroulant au profit d'une saisie semi-automatique
- Module MAIL : Des « : » ont été ajoutés après « objet », « message » et « envoyé le »
- Module MAIL : La taille de l'ensemble du mail est indiquée
- Module MAIL : L'ensemble des pièces jointes n'est plus indiquée par « mail.zip » mais par « Tous les fichiers »
- Module MAIL : Mise à jour de la documentation API

## 10.8.3.7 - 2009-08-26

- Hélios : corrections pour respecter l'API webservice

## 10.8.3.6 - 2009-07-22

- Hélios : rajout du SHA1 dans l'export CSV de fichiers reçus

## 10.8.3.5 2009-06-25

- correction sur la verification de la taille de l'archive pour Actes
- amélioration 297 : on affiche le numéro d'actes dans la liste des transactions en cours
- amélioration 300 : verification de la validité du numéro SIREN

## 10.8.3.3 - 2009-06-23

- correction des bugs sur ACTES liés à une mauvaise configuration du serveur du MIOCT pour les collectivites Corses. (Bug 308)
- correction sur Hélios de la methode de rappatriement du PES de rejet (Bug 302)
- correction sur Hélios sur le PES ACK. On interrogeait pas le bon tag dans le XML. (Bug 301)

## 10.8.3.2 - 2009-06-15

- modification de l'envoi des paramètres à l'applet de signature

## (Servlet)10.8.3 - 2009-04-29

- changement du mode de connexion utilisé pour le FTP vers la DGFIP

## 10.8.3.1 - 2009-03-19

- modification du simulateur

## 10.8.3 - 2009-03-10

- Servlet : corrigé la méthode de log
- Module ACTES : Corrigé les bugs 265,267,268,269,274,275,276
- Module HELIOS : Modifié la fonction pour se connecter au serveur FTP de la DGFIP
- Module HELIOS : Corrigé le bug 271
- Module Mail : Corrigé le bug 273

## 10.8.1 - 2009-01-16

- Module HELIOS : Création des APIs pour Helios
- Module HELIOS : Modification du validateur XML

## 10.8.0 - 2008-12-22

- Module Helios  : Ajout du module Helios
- Module ACTES : Mise à jour du module Acte et de son simulateur vers Acte 1.4(en test)

## 10.7.1 - 2008-06-03

- Module Mail  : Ajout du Module Mail
- Module Admin : Corrigé des bugs Admin;(bug ID:209,196,146)
- Module ACTES : Corrigé des bugs du module Actes;(bug ID:187,214, 218, 211, 194, 199,197, (219->190))

### Limitations connues

- Module Actes - Les noms de fichiers transmis ne peuvent contenir de caractères '.
- Module Actes - L'objet ne peut pas contenir le caractère spécial &.
- Module Actes - Un administrateur de groupe ne peut modifier son profil. Il ne peut créer que des utilisateurs de sa collectivité.

## 10.4 - 2007-12-13

- Module ACTES : Fin de la suppression des archives dont toutes les enveloppes sont acquittées.
- Module ACTES : Ajout de la possibilité d'associer des pièces jointes lors de la création d'une transaction à partir d'un lot
- Module ACTES : Gestion évoluée des mails de notification d'acquittement

## 10.2 - 2007-02-16

- Implémentation administration 3 niveaux, ajout d'un nouveau rôle « Administrateur de groupe »
- Ajout d'une adresse de messagerie pour diffusion d'informations dans les collectivités
- Module ACTES : ajout possibilité de télécharger les fichiers des transactions (archive totale ou fichiers indépendants) pendant la durée de vie de la transaction
- Module ACTES : ajout validation/refus par lot des transactions
- Module ACTES : ajout d'un attribut URL d'archivage pour les transactions de transmission d'acte
- Module ACTES : ajout traitement par lot des transmissions d'actes
- Module ACTES : ajout filtre sur dates de postage et d'accusé réception dans la liste des transactions
- Module ACTES : correction import incorrect des classifications, affichage désordonné et bug javascript lors de la présence de guillemet double
- Module ACTES : ajout possibilité de désactiver dans la configuration la limitation de une seule demande de classification par jour

## 10.1 - 2006-10-27

- Ajout possibilité de récupérer le fichier XML de la classification matières/sous-matières
- Les deux premiers codes de classification matières/sous-matières sont obligatoires
- Ajout authentification par login/password vers le ministère

## 10 - 2006-10-01

- Publication initiale
- Support complet protocole Actes

## Notes

Toutes les modifications apportées au projet seront documentées dans ce fichier.

Le format est basé sur le modèle [Keep a Changelog](http://keepachangelog.com/) et adhère aux principes du [Semantic Versioning](http://semver.org/).
