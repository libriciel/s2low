# Change Log

## 3.0.10 

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

### Évolutions

- Le lien vers les logs est présent quelque soit le statut ~actes #281
- Le changement du nombre d'éléments affiché par page ramène à la page 1 #75
- La colonne fax est remplacé par la colonne SIREN sur la liste des collectivités

### Ajouts

- Méthode /api/info-connexion.php permettant de récupérer les informations sur l'utilisateur et la collectivité de la connexion #239
- Ajout du passage manuel en erreur dans le journal des évenements #81

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

*Cette version nécessite une modification de la base de données*

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

# 3.0.6 - 2018-04-10

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

*Cette version nécessite une modification de la base de données*

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


## 3.0.0-rc3 - 2017-11-15

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


## 3.0.0-rc2

## Corrections
- Liste des PES Retour, filtre par défaut à *tous les états* à la place de *non lu*. ~Helios #253
- Typo #241
- Modification libéllé réponse au message ministère ~Actes #194
- Seul l'admin de groupe et le super admin peuvent uploader une convention @ctes ~Actes #147
- Correction d'un warning lors du post d'un acte hors 7-1 avec un fichier XML et une annexe XML ~Actes #251
- Typo #250, #247, #255, #246, #245, #242, #248
- MIOCT est remplacé par la constante ACTES_MINISTERE_ACRONYME


## [3.0.0-rc1] - 2017-09-18

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
<num_unique_acte>-<type transaction>-<identifiant s2low>-reponse.xml à la place de retour.xml ~Actes
- Mise en place du nouveau schéma Actes V2.2 #177 ~Actes
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

## [2.6.6] - 2017-10-02

### Corrections
- Prise en compte des cas où les PES_ALLER ne sont plus sur le serveur



## [2.6.5] - 2017-06-29

### Ajouts
- Utilisation d'un stockage objet pour les PES Aller

### Corrections
- Correction d'un bug provoquant une erreur si deux mails sécurisés étaient envoyés à la même seconde. ~mailsec
- Suppression de l'autocomplete sur les mots de passe de la création d'utilisateur (bug Firefox)
- Suppression d'un bug potentiel dans la liste des actes sur les actes à l'état 21 (document recu (pas d'AR)) ~Actes
- Message d'erreur sur un upload de fichier PES Aller qui se serait mal passer ou qui est vide ~Helios
- Correction de fautes d'orthographes

## [2.6.4] - 2017-06-14

### Corrections

- Correction d'un bug d'affichage empechant de répondre au message de type courrier simple ~Actes

## [2.6.3] - 2017-06-09


### Évolutions

- Mise à jour du schéma PES en version 5.3 ~Helios

## [2.6.2] - 2017-06-08

## Ajouts
- script de monitoring des fichiers Actes et Helios restés trop longtemps dans un certain état ~Actes ~Helios

### Evolutions
- création d'un pipeline d'intégration continue
- les fichiers version.txt et revision.txt sont remplacé par manifest.txt géré automatiquement par gitlab
- la nouvelle bannière introduite en version 2.5.0 devient la bannière par défaut
- modification du système de mise à jour de la base de données.

## Corrections
- correction de warning dans le code

## [2.6.1] - 2017-05-23

### Ajouts
- le super-admin peut ajouter et publier un message d'information sur le back-office visible pour tous les utilisateurs ~Administration

### Evolutions
- migration du code source vers git



## [2.6.0] - 2017-04-13

### Evolutions
- Vérification de l'unicité des PES ALLER, la vérification de l'unicité des PES ALLER se fait désormais sur
(NomFic, CodCol) et plus uniquemnet sur (NomFic). ~Helios
- Le script de migration (script/migration/v2.5-to-v2.6.php) permet  de mettre à jour la base de données.

# Notes

Toutes les modifications apportées au projet seront documentées dans ce fichier.

Le format est basé sur le modèle [Keep a Changelog](http://keepachangelog.com/) et adhère aux principes du [Semantic Versioning](http://semver.org/).


[non publié]: https://gitlab.libriciel.fr/s2low/s2low/tree/master
[3.0.0-rc1]: https://gitlab.libriciel.fr/s2low/s2low/tags/3.0.0-rc1
[2.6.6]: https://gitlab.libriciel.fr/s2low/s2low/tags/V2.6.6
[2.6.5]: https://gitlab.libriciel.fr/s2low/s2low/tags/V2.6.5
[2.6.4]: https://gitlab.libriciel.fr/s2low/s2low/tags/V2.6.4
[2.6.3]: https://gitlab.libriciel.fr/s2low/s2low/tags/V2.6.3
[2.6.2]: https://gitlab.libriciel.fr/s2low/s2low/tags/V2.6.2
[2.6.1]: https://gitlab.libriciel.fr/s2low/s2low/tags/V2.6.1
[2.6.0]: https://scm.adullact.net/anonscm/svn/s2low/TedetisPHP/tags/V2.6/
