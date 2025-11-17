# Journal de Refactoring - Système d'Authentification

Date de début: 2025-11-17

## Actions effectuées

1. **Création du fichier de suivi** - `refacto-auth.md` créé pour tracker toutes les actions de la refonte
2. **Installation de Symfony Security Bundle** - `composer.json` - Installation de symfony/security-bundle v7.2.9 avec --ignore-platform-reqs
3. **Configuration de Symfony Security** - `config/packages/security.yaml` - Configuration des password_hashers, providers, firewalls, access_control et role_hierarchy
4. **Création de l'entité User Symfony** - `src/Entity/User.php` - Création de la classe User qui implémente UserInterface et PasswordAuthenticatedUserInterface, wrappant la classe User legacy
5. **Création du S2lowUserProvider** - `src/Security/S2lowUserProvider.php` - Création du provider personnalisé qui charge les utilisateurs depuis la base de données legacy
6. **Vérification UserSQL** - `model/UserSQL.php` - Vérification que toutes les méthodes nécessaires existent (queryOne, getIdsAndPasswordsFromConnexionInfo, etc.)
7. **Création du CertificateAndCredentialsAuthenticator** - `src/Security/CertificateAndCredentialsAuthenticator.php` - Création de l'authenticator qui gère certificat SSL + login/password + nonce
8. **Création du SecurityController** - `src/Controller/SecurityController.php` - Création du contrôleur avec les routes /login, /logout et /
9. **Création du template login** - `templates/security/login.html.twig` - Création du template de la page de connexion avec affichage des infos de certificat
10. **Création du template home** - `templates/security/home.html.twig` - Création du template de la page d'accueil après connexion
11. **Création du ModuleVoter** - `src/Security/ModuleVoter.php` - Création du voter pour gérer les permissions VIEW, EDIT et GRANT sur les modules

## Résumé de la refonte

### Fichiers créés

1. **Configuration**:
   - `config/packages/security.yaml` - Configuration complète du système de sécurité Symfony

2. **Entités et Sécurité**:
   - `src/Entity/User.php` - Entité User Symfony wrappant la classe legacy
   - `src/Security/S2lowUserProvider.php` - Provider personnalisé pour charger les utilisateurs
   - `src/Security/CertificateAndCredentialsAuthenticator.php` - Authenticator gérant certificat + credentials + nonce
   - `src/Security/ModuleVoter.php` - Voter pour les permissions par module

3. **Contrôleurs**:
   - `src/Controller/SecurityController.php` - Contrôleur pour login/logout/home

4. **Templates**:
   - `templates/security/login.html.twig` - Page de connexion
   - `templates/security/home.html.twig` - Page d'accueil

### Fonctionnalités implémentées

✅ Authentification par certificat SSL client (avec possibilité de certificat partagé)
✅ Authentification par login/password (toujours active)
✅ Authentification par nonce (liens temporaires)
✅ Gestion des mots de passe legacy MD5 avec migration automatique vers bcrypt
✅ Système de rôles Symfony (ROLE_SADM, ROLE_GADM, ROLE_ADM, ROLE_USER, ROLE_ARCH)
✅ Voter pour permissions par module (VIEW, EDIT, GRANT)
✅ Vérification du statut actif (user + authority + group)
✅ Interface de connexion moderne avec affichage des infos de certificat

### Prochaines étapes

1. **Tests**: Créer des tests unitaires et fonctionnels pour le nouveau système
2. **Migration progressive**: Activer Symfony Security en parallèle du système legacy
3. **Documentation**: Documenter le nouveau système pour l'équipe
4. **Nettoyage**: Une fois stable, désactiver l'ancien système d'authentification

### Notes importantes

- Les services sont automatiquement découverts grâce à l'autowiring configuré dans `config/services.yaml`
- La classe User legacy reste fonctionnelle et est wrappée par la nouvelle entité Symfony
- Le système est compatible avec le code existant via la méthode `getLegacyUser()`
- Les certificats SSL restent obligatoires (configurés dans Apache)
