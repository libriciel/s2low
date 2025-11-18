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
- **Les certificats SSL sont maintenant FACULTATIFS** (configurés dans Apache avec `SSLVerifyClient optional`)

## Corrections apportées

### 17/11/2025 - Correction de findByLoginOrEmail()
**Problème**: `ErrorException: Warning: Trying to access array offset on value of type int` à la ligne 137 de S2lowUserProvider.php

**Cause**: La méthode `queryOne()` retourne directement la valeur de l'ID (un entier) quand il n'y a qu'une seule colonne dans le SELECT, et non un tableau.

**Solution**: Modification de `findByLoginOrEmail()` pour traiter correctement le retour de `queryOne()` :
- `$userId = $this->userSQL->queryOne(...)` au lieu de `$result = ...`
- `if ($userId !== false)` au lieu de `if ($result)`
- `$this->userSQL->getInfo($userId)` au lieu de `$this->userSQL->getInfo($result['id'])`

**Fichier modifié**: `src/Security/S2lowUserProvider.php` lignes 122-142

### 17/11/2025 - Configuration de la session Symfony
**Problème**: `BadRequestHttpException: Session has not been set.`

**Cause**: La session n'était pas configurée dans `framework.yaml`, ce qui est requis pour Symfony Security.

**Solution**:
1. Ajout de la configuration de session dans `config/packages/framework.yaml`
2. Sécurisation de l'accès à la session dans l'authenticator avec `$request->hasSession()` avant d'utiliser `getSession()`

**Fichiers modifiés**:
- `config/packages/framework.yaml` - Ajout de la configuration session
- `src/Security/CertificateAndCredentialsAuthenticator.php` lignes 181-199 - Vérification hasSession() avant accès

### 17/11/2025 - Problème de sérialisation PDO
**Problème**: `Exception: Serialization of 'PDO' is not allowed`

**Cause**: L'entité User contient une référence à l'objet User legacy, qui lui-même contient une connexion PDO. Symfony tente de sérialiser l'utilisateur complet en session, ce qui échoue car PDO ne peut pas être sérialisé.

**Solution**: Implémentation de `__serialize()` et `__unserialize()` dans l'entité User :
- `__serialize()` : Sérialise uniquement les données primitives (id, email, login, etc.) sans l'objet legacy
- `__unserialize()` : Restaure les données et recrée l'objet legacy User en le rechargeant depuis la base de données

**Fichier modifié**: `src/Entity/User.php` lignes 183-225

### 17/11/2025 - Boucle de redirection infinie
**Problème**: `ERR_TOO_MANY_REDIRECTS` - Chrome détecte une boucle de redirection infinie

**Cause**: La méthode `supports()` de l'authenticator retournait `true` pour toutes les requêtes, y compris `/login`. Quand l'authentification échouait, elle redirigait vers `/login` qui déclenchait à nouveau l'authentification, créant une boucle infinie.

**Solution**:
1. Modification de `supports()` pour ne supporter que les POST sur `/login` (soumission du formulaire)
2. Ajout de `entry_point` dans `security.yaml` pour définir où rediriger les utilisateurs non authentifiés
3. Implémentation de `AuthenticationEntryPointInterface` avec la méthode `start()` dans l'authenticator

**Fichiers modifiés**:
- `src/Security/CertificateAndCredentialsAuthenticator.php` lignes 36-41 (supports), lignes 199-207 (start)
- `config/packages/security.yaml` ligne 22 - Ajout de entry_point

### 17/11/2025 - Erreur 404 sur /login
**Problème**: 404 Not Found sur la route `/login`

**Cause**: Le fichier `config/routes/annotations.yaml` utilisait `type: annotation` (ancienne méthode) au lieu de `type: attribute` (méthode moderne avec PHP 8 et les attributs #[Route]).

**Solution**:
1. Modification de `config/routes/annotations.yaml` : changement de `type: annotation` vers `type: attribute`
2. Vidage du cache Symfony avec `docker compose exec web php bin/console cache:clear`

**Fichier modifié**: `config/routes/annotations.yaml` lignes 3-4 et 7-8

### 17/11/2025 - Conflit avec login.php legacy
**Problème**: La page `/login` retourne toujours 404 malgré la route enregistrée

**Cause**: Le fichier `public.ssl/login.php` legacy existe. Apache sert directement ce fichier car les règles de réécriture dans la configuration Apache (ligne 93-95 de s2low-apache-config.conf) stipulent : "si le fichier existe ET que ce n'est pas un .php, ne pas rediriger". Comme `login.php` existe, Apache le sert au lieu de router vers Symfony.

**Solution**: Changement temporaire de la route de `/login` vers `/security/login` pour éviter le conflit avec le fichier legacy :
- Route du contrôleur : `/security/login`
- Access control dans security.yaml : `^/security/login`
- Méthode supports() de l'authenticator : `/security/login`

**Fichiers modifiés**:
- `src/Controller/SecurityController.php` ligne 24
- `config/packages/security.yaml` ligne 40
- `src/Security/CertificateAndCredentialsAuthenticator.php` ligne 41

**Note**: À terme, il faudra soit :
1. Supprimer le fichier `public.ssl/login.php` legacy une fois la migration terminée
2. Ajouter une règle de réécriture Apache spécifique pour forcer `/login` vers Symfony

### 17/11/2025 - Correction du comportement d'authentification certificat/credentials
**Problème**: Login/mot de passe demandé systématiquement même avec un certificat unique

**Cause**: Le système actuel ne respectait pas le comportement voulu :
- Certificat SSL était obligatoire (Apache `SSLVerifyClient require`)
- Login/mot de passe était toujours demandé, même si un seul utilisateur avait le certificat

**Comportement voulu**:
1. Certificat SSL FACULTATIF
2. SI certificat présent ET un seul utilisateur => authentification automatique (pas de login/mdp)
3. SI certificat présent ET plusieurs utilisateurs => login/mdp requis (cas legacy du double user)
4. SI pas de certificat => login/mot de passe obligatoire

**Solution**:
1. Modification Apache : `SSLVerifyClient require` → `SSLVerifyClient optional` dans `docker-resources/apache/site-available/s2low-apache-config.conf` ligne 65
2. Modification de `CertificateAndCredentialsAuthenticator::supports()` : supporte les requêtes POST sur `/security/login` OU les requêtes avec certificat
3. Ajout de la méthode `hasCertificate()` pour détecter la présence d'un certificat valide
4. Refonte complète de `CertificateAndCredentialsAuthenticator::authenticate()` avec 3 cas distincts :
   - **CAS 1a**: Certificat présent + login/password fournis → authentification certificat + credentials (cas legacy double user)
   - **CAS 1b**: Certificat présent + pas de credentials → tentative d'authentification par certificat seul (réussit si un seul utilisateur)
   - **CAS 2**: Pas de certificat → login/password obligatoire (authentification classique par identifiant)

**Fichiers modifiés**:
- `docker-resources/apache/site-available/s2low-apache-config.conf` ligne 65
- `src/Security/CertificateAndCredentialsAuthenticator.php` lignes 37-130
