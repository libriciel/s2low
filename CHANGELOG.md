# Change Log

Toutes les modifications apportées au projet seront documentées dans ce fichier.

Le format est basé sur le modèle [Keep a Changelog](http://keepachangelog.com/) 
et adhère aux principes du [Semantic Versioning](http://semver.org/).

## [non publié]

### Ajouts

- Création du script cron/actes-analyse-fichier-a-envoyer.php qui vise à remplacer la partie Tomcat #190 ~Actes
- Création du script cron/acte-envoi-fichier.php pour remplacer la partie Tomcat #192 ~Actes
- Création du script cron/acte-reception-fichier.php pour remplacer la partie Tomcat #195 ~Actes
- Création du script cron/acte-analyse-ficier-recu.php pour remplacer la partie Tomcat #197 ~Actes
- Visualisation rapide de l'état des transactions Actes #90 ~Actes
- Le super admin peut envoyer des demandes de classification via la console (sur la page de modification d'une collectivité) ~admin ~Actes
- Le super admin peut envoyer une demande de classification forcé à vide (pas de date de classification) ~admin ~Actes


### Corrections de sécurités

- Correction d'une faille de sécurité sur les modules ~admin

### Évolutions

- La validation de la signature des PES Aller n'affiche plus une erreur si le fichier n'est pas signé #188 ~admin 
- Les admin sont autorisés à récupérer les PES Retour via l'API #164 ~Helios
- modification du libellé des natures d'actes dans le filtre de recherche #163 ~Actes
- Sur la visualtion d'une transaction, le lien de téléchargement des archives transmisses passent du titre (Fichiers contenus dans l'archive transmise) en bas 
du tableau des fichiers contenus dans l'archive ~Actes
- Il est maintenant possible de télécharger l'archive transmisse dans le cas des messages 7-1 (demande de classification) ~Actes
- Les messages de classification passent en acquittement recu. Ce mécanisme n'est fiable que dans les cas où il n'y a qu'une enveloppe en cours. ~Actes
- Il est possible de récupérer le fichier de classification directement sur la console ~Actes ~admin
- Par défaut, le filtre de la liste des actes est mis à "tous les états" à la place de "en cours" #101 ~Actes
- La fonction de l'API helios_transac_get_status.php complete le champs message afin d'y mettre le message d'erreur 
ou le message de passage dans l'état #110 ~Helios  

### Corrections

- Correction d'une lettre f qui apparaissait sur l'édition d'une collectivité #187 ~admin
- Le status de l'archivage des actes est considéré à tort comme refusé pour les bordereau SEDA 1.0 #146 ~Actes  
- La date d'acquittement du tampon est prise dans l'ARActe #173 ~Actes
- Mise à jour du libellé du status 11 - Aquittement de document reçu -> Acquittement de document reçu #158 ~Actes
- Bug sur l'attribution des PES Acquit (mode NomFic non unique) #196 ~Helios
- Correction de l'orthographe des notes de mise à jour de version #89
- Possibilité d'accéder à un message d'urgence dont le titre est vide #200 ~admin

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
- le super-admin peut ajouter et publier un message d'information sur le back-office visible pour tous les utilisateurs ~admin

### Evolutions
- migration du code source vers git



## [2.6.0] - 2017-04-13

### Evolutions
- Vérification de l'unicité des PES ALLER, la vérification de l'unicité des PES ALLER se fait désormais sur 
(NomFic, CodCol) et plus uniquemnet sur (NomFic). ~Helios
- Le script de migration (script/migration/v2.5-to-v2.6.php) permet  de mettre à jour la base de données.


[non publié]: https://gitlab.libriciel.fr/s2low/s2low/tree/master
[2.6.5]: https://gitlab.libriciel.fr/s2low/s2low/tags/V2.6.5
[2.6.4]: https://gitlab.libriciel.fr/s2low/s2low/tags/V2.6.4
[2.6.3]: https://gitlab.libriciel.fr/s2low/s2low/tags/V2.6.3
[2.6.2]: https://gitlab.libriciel.fr/s2low/s2low/tags/V2.6.2
[2.6.1]: https://gitlab.libriciel.fr/s2low/s2low/tags/V2.6.1
[2.6.0]: https://scm.adullact.net/anonscm/svn/s2low/TedetisPHP/tags/V2.6/