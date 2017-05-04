# Change Log

Toutes les modifications apportées au projet seront documentées dans ce fichier.

Le format est basé sur le modèle [Keep a Changelog](http://keepachangelog.com/) 
et adhère aux principes du [Semantic Versioning](http://semver.org/).

## [Unreleased] - 2017-05-04

### Changed
- migration du code source vers git

### Added
- le super-admin peut ajouter et publier un message d'information sur le back-office visible pour tous les utilisatuers


## [2.6.0] - 2017-04-13
### Changed
- Vérification de l'unicité des PES ALLER, la vérification de l'unicité des PES ALLER se fait désormais sur  (NomFic, CodCol) et plus uniquemnet sur (NomFic).
- Le script de migration (script/migration/v2.5-to-v2.6.php) permet  de mettre à jour la base de données.



[Unreleased]: https://gitlab.libriciel.fr/s2low/s2low/tree/master
[2.6.0]: https://scm.adullact.net/anonscm/svn/s2low/TedetisPHP/tags/V2.6/