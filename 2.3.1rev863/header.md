

**TéDéTIS** est un logiciel de tiers de télétransmission multiprotocoles gérant les flux administratifs entre les
collectivités territoriales et les administrations centrales.

Connexion à la plateforme TéDéTIS
-----


L'application désirant utiliser cette interface devra être capable d'établir une connexion TCP vers le port 443
du serveur Web exécutant le logiciel TéDéTis. Elle devra ensuite effectuer une négociation SSL avec
authentification mutuelle.
Pour cela elle devra présenter un certificat numérique reconnu par la plateforme
pour s'authentifier en tant
qu'utilisateur attaché à une collectivité. Un compte utilisateur devra donc avoir été créé préalablement sur la
plateforme
pour la détermination des droits d'accès et de l'appartenance à une collectivité. Le certificat doit avoir
été importé sur le serveur TdT et doit donc être compatible avec la liste des certificats acceptés.
À partir de ce point, le dialogue HTTP peut avoir lieu au dessus de la connexion SSL.
Des bibliothèques de programmation permettent d'effectuer ce travail fastidieux de connexion, par exemple
CURL en C ou Jakarta HTTPClient en Java.

Hélios
------


Hélios représente son module de dématérialisation de la chaîne comptable et financière.
Ce module gère le flux de transmission d'un document d'un ordonnateur vers un comptable.


L'usage du module Hélios du TdT se fait à travers une API programme depuis un logiciel métier.


Ce document décrit l’API HTTPS pour exploiter le module Hélios. Cet interface est utilisée par les applicatifs
métier par le biais de bibliothèques génériques implémentant le protocole HTTP.


Hélios implémente le protocole PES2 sans couche de transport propriétaire.