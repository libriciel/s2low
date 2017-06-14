# Change Log

Toutes les modifications apport�es au projet seront document�es dans ce fichier.

Le format est bas� sur le mod�le [Keep a Changelog](http://keepachangelog.com/) 
et adh�re aux principes du [Semantic Versioning](http://semver.org/).

## [non publi�]

- Utilisation d'un stockage objet pour les PES Aller
- Correction d'un bug provoquant une erreur si deux mails s�curis�s �taient envoy�s � la m�me seconde.
- Suppression de l'autocomplete sur les mots de passe de la cr�ation d'utilisarteur (bug Firefox)
- Suppression d'un bug potentiel dans la liste des actes sur les actes � l'�tat 21 (document recu (pas d'AR))
- Message d'erreur sur un upload de fichier PES Aller qui se serait mal passer ou qui est vide
- Correction faute d'orthographe
## [2.6.4] - 2017-06-14
- Correction d'un bug d'affichage empechant de r�pondre au message de type courrier simple 
 
## [2.6.3] - 2017-06-09
 
- Mise � jour du sch�ma PES en version 5.3
 
## [2.6.2] - 2017-06-08

### Evolutions
- cr�ation d'un pipeline d'int�gration continue
- les fichiers version.txt et revision.txt sont remplac� par manifest.txt g�r� automatiquement par gitlab 
- la nouvelle banni�re introduite en version 2.5.0 devient la banni�re par d�faut
- modification du syst�me de mise � jour de la base de donn�es.

## Corrections
- correction de warning dans le code

## Ajouts
- script de monitoring des fichiers Actes et Helios rest�s trop longtemps dans un certain �tat.

## [2.6.1] - 2017-05-23

### Evolutions
- migration du code source vers git


### Ajouts
- le super-admin peut ajouter et publier un message d'information sur le back-office visible pour tous les utilisatuers


## [2.6.0] - 2017-04-13
### Evolutions
- V�rification de l'unicit� des PES ALLER, la v�rification de l'unicit� des PES ALLER se fait d�sormais sur  (NomFic, CodCol) et plus uniquemnet sur (NomFic).
- Le script de migration (script/migration/v2.5-to-v2.6.php) permet  de mettre � jour la base de donn�es.


[non publi�]: https://gitlab.libriciel.fr/s2low/s2low/tree/master
[2.6.2]: https://gitlab.libriciel.fr/s2low/s2low/tags/V2.6.2
[2.6.1]: https://gitlab.libriciel.fr/s2low/s2low/tags/V2.6.1
[2.6.0]: https://scm.adullact.net/anonscm/svn/s2low/TedetisPHP/tags/V2.6/