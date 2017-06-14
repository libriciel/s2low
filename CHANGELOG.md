# Change Log

Toutes les modifications apportées au projet seront documentées dans ce fichier.

Le format est basé sur le modèle [Keep a Changelog](http://keepachangelog.com/) 
et adhère aux principes du [Semantic Versioning](http://semver.org/).

## [non publié]

- Utilisation d'un stockage objet pour les PES Aller
- Correction d'un bug provoquant une erreur si deux mails sécurisés étaient envoyés à la même seconde.
- Suppression de l'autocomplete sur les mots de passe de la création d'utilisarteur (bug Firefox)
- Suppression d'un bug potentiel dans la liste des actes sur les actes à l'état 21 (document recu (pas d'AR))
- Message d'erreur sur un upload de fichier PES Aller qui se serait mal passer ou qui est vide

 
## [2.6.3] - 2017-06-09
 
- Mise à jour du schéma PES en version 5.3
 
## [2.6.2] - 2017-06-08

### Evolutions
- création d'un pipeline d'intégration continue
- les fichiers version.txt et revision.txt sont remplacé par manifest.txt géré automatiquement par gitlab 
- la nouvelle bannière introduite en version 2.5.0 devient la bannière par défaut
- modification du système de mise à jour de la base de données.

## Corrections
- correction de warning dans le code

## Ajouts
- script de monitoring des fichiers Actes et Helios restés trop longtemps dans un certain état.

## [2.6.1] - 2017-05-23

### Evolutions
- migration du code source vers git


### Ajouts
- le super-admin peut ajouter et publier un message d'information sur le back-office visible pour tous les utilisatuers


## [2.6.0] - 2017-04-13
### Evolutions
- Vérification de l'unicité des PES ALLER, la vérification de l'unicité des PES ALLER se fait désormais sur  (NomFic, CodCol) et plus uniquemnet sur (NomFic).
- Le script de migration (script/migration/v2.5-to-v2.6.php) permet  de mettre à jour la base de données.


[non publié]: https://gitlab.libriciel.fr/s2low/s2low/tree/master
[2.6.2]: https://gitlab.libriciel.fr/s2low/s2low/tags/V2.6.2
[2.6.1]: https://gitlab.libriciel.fr/s2low/s2low/tags/V2.6.1
[2.6.0]: https://scm.adullact.net/anonscm/svn/s2low/TedetisPHP/tags/V2.6/