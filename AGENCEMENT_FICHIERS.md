# Arborescence du projet

```

|-- /S2low
|   |-- /Domain
|   |   |-- /Port 
|   |   |   |   (Interfaces exposées par le domaine, utilisées par l'application et les services)
|   |   |-- /Exception 
|   |   |   |   (Exceptions spécifiques au domaine métier)
|   |   |-- /Model 
|   |   |   |   (Représentation des concepts métier et logique associée)
|   |   |   |-- /ValueObject 
|   |   |   |   |   (Objets de valeur immuables, définissant des propriétés sans identité propre)
|   |   |-- /Repository 
|   |   |   |   (Interfaces des repositories du domaine, définissant les accès aux données)
|   |   |-- /Service 
|   |   |   |   (Logique métier partagée entre plusieurs modèles)
|   |-- /Application
|   |   |-- /UseCase 
|   |   |   |   (Orchestration des cas d'utilisation, en manipulant les modèles, 
|   |   |   |   les repositories et les adaptateurs pour appliquer la logique métier)
|   |-- /Infrastructure
|   |   |-- /Persistence
|   |   |   |-- /Repository
|   |   |   |   |   (Implémentations concrètes des repositories, accédant aux données via une base)
|   |   |   |   |   RepositoriesA.php
|   |   |   |   |   RepositoriesB.php
|   |   |   |-- /Entity 
|   |   |   |   |   (Entités correspondant aux tables de la base de données.
|   |   |   |   |   Ces objets sont purement liés à la persistance et non au domaine métier.)
|   |   |   |   |   EntityA.php
|   |   |   |   |   EntityB.php
|   |   |-- /Adapter
|   |   |   |-- /HttpClient 
|   |   |   |   |   (Clients permettant d’interagir avec des API externes)
|   |   |   |   |   ClientA.php
|   |   |   |   |   ClientB.php
|   |   |   |-- /Filesystem 
|   |   |   |   |   (Gestion des fichiers locaux ou distants)
|   |   |   |-- /Email 
|   |   |   |   |   (Gestion de l'envoi d'emails via différents protocoles)


```