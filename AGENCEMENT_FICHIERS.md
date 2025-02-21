# Arborescence du projet

```
|-- /S2low
|   |-- /Domain
|   |   |-- /Model (Concept metier et logique associé)
|   |   |-- /Service (Logique metier touchant a plusieurs Model)
|   |   |-- /Exception (Exception rattaché a des models)
|   |-- /Application
|   |   |-- /Service (Services orchestrant la logique fonctionnel. Utilisation d'adapter, d'entite, de models)
|   |-- /Persistence
|   |   |   |-- /Entity
|   |   |   |   |   EntityA
|   |   |   |   |   EntityB
|   |   |   |-- /Repository
|   |   |   |   |   RepositoriesA
|   |   |   |   |   RepositoriesB
|   |-- /Adapter
|   |   |-- /HttpClient (Client API externes)
|   |   |   |   ClientA.php
|   |   |   |   ClientB.php
|   |   |-- /Filesystem ( Gestion des fichiers)
|   |   |-- /Email ( Gestion des Mails)

```