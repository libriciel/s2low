# Prompt de Migration de l'Authentification vers Symfony Security

## Contexte du Projet

Application PHP legacy (S2low) utilisant Symfony 6.4, nécessitant une migration de son système d'authentification personnalisé vers Symfony Security.

## Système d'Authentification Actuel

### Architecture Actuelle

#### 1. Configuration Apache (docker-resources/apache/site-available/s2low-apache-config.conf)

```apache
<VirtualHost *:8443>
    SSLEngine on
    SSLVerifyClient require          # Certificat client OBLIGATOIRE
    SSLVerifyDepth 5
    SSLCACertificatePath  /etc/s2low/ssl/validca
    SSLCARevocationPath /etc/s2low/ssl/validca
    SSLCARevocationCheck chain
    SSLOptions +StdEnvVars +OptRenegotiate +ExportCertData +LegacyDNStringFormat
</VirtualHost>
```

**Points clés** :
- Certificat SSL client requis pour TOUTES les connexions
- Variables d'environnement Apache disponibles : `SSL_CLIENT_CERT`, `SSL_CLIENT_S_DN`, `SSL_CLIENT_I_DN`, `SSL_CLIENT_VERIFY`
- Possibilité d'authentification HTTP Basic (`PHP_AUTH_USER`, `PHP_AUTH_PW`)

#### 2. Méthodes d'Authentification Supportées

**a) Authentification par certificat uniquement** (`AUTHENTIFICATION_BY_APACHE = 1`)
- Utilisateur identifié uniquement par le hash du certificat
- Utilisé quand un seul utilisateur possède ce certificat
- Pas de login/mot de passe requis

**b) Authentification par certificat + login/mot de passe** (`AUTHENTIFICATION_BY_FORM = 2`)
- Plusieurs utilisateurs peuvent partager le même certificat
- Différenciation par login + mot de passe
- Formulaire de connexion dans `public.ssl/login.php`
- Soumission vers `public.ssl/ident.php`

**c) Authentification par nonce** (liens temporaires)
- Système de liens temporaires valides 5 minutes
- Utilise la table `nounce` (login, nounce, hash SHA256, authority_id)
- Permet l'accès sans re-saisie des credentials

#### 3. Flux d'Authentification Actuel

```
1. Utilisateur accède à l'application → Apache vérifie certificat SSL
2. class/Authentification.php::authenticate() appelé
3. Vérification session : si 'id_login' existe → verifConnexion()
4. Sinon → detectConnexionID() :
   a. Vérifie si nonce dans URL → getConnexionIdFromNounce()
   b. Récupère infos certificat → HttpsConnexion::getCertificateInfo()
   c. Récupère credentials (Apache ou POST) → getAllConnexionInfo()
   d. Recherche utilisateur(s) en base → getIdFromConnexionInfo()
      - Si login/password fournis → vérification avec PasswordHandler
      - Sinon → identification par certificat seul
   e. Si 0 utilisateurs → Erreur "certificat invalide"
   f. Si > 1 utilisateur → Redirection vers login.php
   g. Si 1 utilisateur → Authentification réussie
5. Stockage id_login en session
```

#### 4. Structure de la Base de Données

**Table `users`** :
```sql
- id (PK)
- email
- subject_dn              # DN du certificat
- issuer_dn               # DN du fournisseur du certificat
- name                    # Nom
- givenname               # Prénom
- login                   # Login (optionnel)
- password                # Hash bcrypt (optionnel, peut être MD5 legacy)
- role                    # SADM, GADM, ADM, USER, ARCH
- telephone
- authority_group_id      # Groupe (optionnel)
- authority_id            # Collectivité (obligatoire)
- status                  # 0=désactivé, 1=activé
- certificate             # Certificat PEM complet
- cert_not_before         # Date début validité
- cert_not_after          # Date fin validité
- cert_serial             # Numéro de série
- certificate_hash        # SHA1 base64 du certificat
- certificate_rgs_2_etoiles # Certificat RGS** (legacy, déprécié)
```

**Table `nounce`** :
```sql
- id (PK)
- nounce                  # Token unique
- login
- hash                    # SHA256(password:nounce)
- creation                # Timestamp
- authority_id
```

**Table `users_perms`** :
```sql
- id (PK)
- user_id
- module_id
- perm                    # NONE, RO, RW, GRANT
```

#### 5. Gestion des Mots de Passe (class/PasswordHandler.php)

```php
public function passwordMatchesHash(string $password, string $hash, int $id): bool
{
    // Support des anciens hashs MD5 (32 caractères)
    if (mb_strlen($hash) === 32) {
        $match = (md5($password) === $hash);
        if ($match) {
            $this->updatePasswordHash($id, $password); // Mise à jour vers bcrypt
        }
        return $match;
    }
    // Nouveaux hashs : bcrypt (PASSWORD_DEFAULT)
    return password_verify($password, $hash);
}
```

#### 6. Rôles et Permissions

**Rôles** (class/User.php) :
- `SADM` : Super administrateur (accès total)
- `GADM` : Administrateur de groupe (gère plusieurs collectivités)
- `ADM` : Administrateur de collectivité
- `USER` : Utilisateur standard
- `ARCH` : Archiviste

**Hiérarchie** :
- SADM > GADM > ADM > USER
- Permissions par module : NONE, RO (lecture), RW (écriture), GRANT (concession)

**Vérifications courantes** :
```php
$user->isSuper()                  // Est SADM ?
$user->isGroupAdmin()             // Est GADM ?
$user->isAdmin()                  // Est SADM|GADM|ADM ?
$user->isAuthorityAdmin()         // Est ADM ?
$user->isArchivist()              // Est ARCH ?
$user->canAccess($module)         // Peut accéder au module ?
$user->canEdit($module)           // Peut modifier le module ?
$user->isActive()                 // Compte activé + collectivité active + groupe actif ?
```

## Objectifs de la Migration

### Fonctionnalités à Conserver

1. ✅ Authentification par certificat SSL client (obligatoire). Elle deviendra cependant facultative. On aura la possibilite de se connecter sur une page dedié.
2. ✅ Authentification complémentaire par login/mot de passe (nouveau : TOUJOURS actif)
3. ✅ Support des certificats partagés (plusieurs users avec même certificat)
4. ✅ Système de nonce pour liens temporaires
5. ✅ Gestion des mots de passe legacy MD5 avec migration automatique vers bcrypt
6. ✅ Système de rôles et permissions par module
7. ✅ Vérification du statut actif (user + authority + group)

### Nouvelles Fonctionnalités Requises

1. **Login/Mot de passe comme méthode principale**
   - Actuellement : login/mdp uniquement si certificat partagé
   - **Nouveau** : TOUJOURS permettre login/mdp, même avec certificat unique
   - Le certificat reste obligatoire (Apache) mais ne suffit plus seul

2. **Migration vers Symfony Security**
   - Utiliser `security.yaml` pour la configuration
   - Créer un `UserProvider` custom
   - Créer un `Authenticator` qui gère certificat + credentials
   - Utiliser les voteurs Symfony pour les permissions
   - Intégrer les rôles Symfony (`ROLE_SADM`, `ROLE_GADM`, etc.)

### Contraintes Techniques

1. **Symfony 6.4** : Utiliser les APIs modernes de Symfony Security
2. **Base de données existante** : Ne PAS modifier le schéma existant
3. **Compatibilité legacy** :
   - Les classes `class/User.php` et `model/UserSQL.php` doivent rester fonctionnelles pendant la migration
   - Migration progressive : dual-system temporaire possible
4. **Pas de Doctrine ORM** : L'application utilise un accès SQL direct (class `SQL`, `SQLQuery`)

## Spécifications d'Implémentation

### 1. Configuration Symfony Security (config/packages/security.yaml)

```yaml
security:
    # Encodeur pour mots de passe
    password_hashers:
        App\Entity\User:
            algorithm: auto  # Utilise bcrypt/argon2 selon disponibilité

    # Provider personnalisé
    providers:
        s2low_user_provider:
            id: App\Security\S2lowUserProvider

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        main:
            lazy: true
            provider: s2low_user_provider

            # Authenticator custom : certificat + credentials
            custom_authenticators:
                - App\Security\CertificateAndCredentialsAuthenticator

            # Logout
            logout:
                path: /logout
                target: /

            # Remember me (optionnel)
            remember_me:
                secret: '%kernel.secret%'
                lifetime: 604800 # 1 semaine

    # Contrôle d'accès
    access_control:
        - { path: ^/login, roles: PUBLIC_ACCESS }
        - { path: ^/ident, roles: PUBLIC_ACCESS }
        - { path: ^/, roles: ROLE_USER }

    # Hiérarchie des rôles
    role_hierarchy:
        ROLE_ARCH: ROLE_USER
        ROLE_ADM: ROLE_USER
        ROLE_GADM: ROLE_ADM
        ROLE_SADM: ROLE_GADM
```

### 2. Entité User Symfony (src/Entity/User.php)

Créer une entité Symfony qui wraps la classe legacy :

```php
namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private int $id;
    private string $email;
    private string $login;
    private ?string $password;
    private string $role;
    private array $permissions = [];
    private string $certificateHash;

    // Référence vers la classe legacy pour compatibilité
    private \S2lowLegacy\Class\User $legacyUser;

    public function __construct(\S2lowLegacy\Class\User $legacyUser)
    {
        $this->legacyUser = $legacyUser;
        $this->id = $legacyUser->get('id');
        $this->email = $legacyUser->get('email');
        $this->login = $legacyUser->get('login');
        $this->password = $legacyUser->get('password');
        $this->role = $legacyUser->get('role');
        $this->certificateHash = $legacyUser->get('certificate_hash');
        // Charger permissions...
    }

    public function getUserIdentifier(): string
    {
        return $this->login ?: $this->email;
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        // Mapping des rôles legacy vers Symfony
        switch ($this->role) {
            case 'SADM':
                $roles[] = 'ROLE_SADM';
                break;
            case 'GADM':
                $roles[] = 'ROLE_GADM';
                break;
            case 'ADM':
                $roles[] = 'ROLE_ADM';
                break;
            case 'ARCH':
                $roles[] = 'ROLE_ARCH';
                break;
        }

        // Ajouter permissions par module comme rôles
        foreach ($this->permissions as $module => $perm) {
            if ($perm['perm'] === 'RW') {
                $roles[] = 'ROLE_MODULE_' . strtoupper($module) . '_WRITE';
            } elseif ($perm['perm'] === 'RO') {
                $roles[] = 'ROLE_MODULE_' . strtoupper($module) . '_READ';
            }
        }

        return array_unique($roles);
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
        // Rien à effacer
    }

    // Méthode pour accéder à l'objet legacy
    public function getLegacyUser(): \S2lowLegacy\Class\User
    {
        return $this->legacyUser;
    }
}
```

### 3. UserProvider Personnalisé (src/Security/S2lowUserProvider.php)

```php
namespace App\Security;

use App\Entity\User;
use S2lowLegacy\Class\User as LegacyUser;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class S2lowUserProvider implements UserProviderInterface
{
    public function __construct(
        private \S2lowLegacy\Model\UserSQL $userSQL
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Recherche par login ou email
        $userData = $this->userSQL->findByLoginOrEmail($identifier);

        if (!$userData) {
            throw new UserNotFoundException();
        }

        $legacyUser = new LegacyUser($userData['id']);
        $legacyUser->init();

        return new User($legacyUser);
    }

    public function loadUserByCertificateHashAndCredentials(
        string $certificateHash,
        ?string $login,
        ?string $password
    ): UserInterface {
        // Logique similaire à Authentification::getIdFromConnexionInfo()
        if ($login && $password) {
            $users = $this->userSQL->getIdsAndPasswordsFromConnexionInfo(
                $certificateHash,
                '',  // certificate_rgs_2_etoiles vide (déprécié)
                $login
            );

            foreach ($users as $userData) {
                // Vérification mot de passe avec PasswordHandler
                $passwordHandler = new \S2lowLegacy\Class\PasswordHandler($this->userSQL);
                if ($passwordHandler->passwordMatchesHash($password, $userData['password'], $userData['id'])) {
                    $legacyUser = new LegacyUser($userData['id']);
                    $legacyUser->init();
                    return new User($legacyUser);
                }
            }

            throw new UserNotFoundException();
        }

        // Si pas de credentials, erreur (nouvelle politique : login/mdp obligatoire)
        throw new UserNotFoundException('Login et mot de passe requis');
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException();
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }
}
```

### 4. Authenticator Personnalisé (src/Security/CertificateAndCredentialsAuthenticator.php)

Implémente `AuthenticatorInterface` pour gérer :
- Extraction du certificat SSL depuis `$_SERVER`
- Extraction du hash du certificat
- Récupération des credentials (formulaire ou HTTP Basic)
- Vérification nonce si présent
- Authentification combinée certificat + login/password

```php
namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class CertificateAndCredentialsAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private S2lowUserProvider $userProvider,
        private \S2lowLegacy\Class\HttpsConnexion $httpsConnexion,
        private \S2lowLegacy\Model\NounceSQL $nounceSQL
    ) {}

    public function supports(Request $request): ?bool
    {
        // Supporte toutes les requêtes (certificat toujours requis)
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        // 1. Vérifier nonce en priorité
        if ($this->httpsConnexion->hasNonceParameters()) {
            return $this->authenticateWithNonce($request);
        }

        // 2. Récupérer infos certificat
        $certInfo = $this->httpsConnexion->getCertificateInfo();
        if (!$certInfo || !$certInfo['certificate_hash']) {
            throw new AuthenticationException('Certificat SSL invalide');
        }

        // 3. Récupérer credentials
        $credentials = $this->getCredentials($request);

        // 4. Authentifier
        if ($credentials['login'] && $credentials['password']) {
            // Authentification avec login/password
            $user = $this->userProvider->loadUserByCertificateHashAndCredentials(
                $certInfo['certificate_hash'],
                $credentials['login'],
                $credentials['password']
            );

            return new Passport(
                new UserBadge($user->getUserIdentifier()),
                new PasswordCredentials($credentials['password'])
            );
        }

        // 5. Sans credentials : afficher formulaire login
        throw new AuthenticationException('Authentification requise');
    }

    private function getCredentials(Request $request): array
    {
        // POST (formulaire)
        if ($request->isMethod('POST')) {
            return [
                'login' => $request->request->get('login'),
                'password' => $request->request->get('password')
            ];
        }

        // HTTP Basic Auth
        return [
            'login' => $request->server->get('PHP_AUTH_USER'),
            'password' => $request->server->get('PHP_AUTH_PW')
        ];
    }

    private function authenticateWithNonce(Request $request): Passport
    {
        $nonceParams = $this->httpsConnexion->getNonceParameters();
        $authorityId = $this->nounceSQL->verify(...$nonceParams);

        if (!$authorityId) {
            throw new AuthenticationException('Nonce invalide ou expiré');
        }

        $certHash = $this->httpsConnexion->getCertificateHash();
        // Récupérer user par certificat + authority
        // ...

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier())
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Rediriger vers page demandée ou home
        if ($targetPath = $request->getSession()->get('_security.main.target_path')) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse('/');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Rediriger vers login avec message d'erreur
        $request->getSession()->set('error', $exception->getMessage());
        return new RedirectResponse('/login');
    }
}
```

### 5. Contrôleur de Login (src/Controller/SecurityController.php)

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Récupérer erreur de login si présente
        $error = $authenticationUtils->getLastAuthenticationError();

        // Dernier username saisi
        $lastUsername = $authenticationUtils->getLastUsername();

        // Récupérer infos certificat pour affichage
        $certInfo = $this->getCertificateInfo();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'certificate' => $certInfo
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Géré automatiquement par Symfony
    }

    private function getCertificateInfo(): ?array
    {
        $httpsConnexion = /* Récupérer service */;
        return $httpsConnexion->getCertificateInfo();
    }
}
```

### 6. Template Login (templates/security/login.html.twig)

```twig
{% extends 'base.html.twig' %}

{% block title %}Connexion{% endblock %}

{% block body %}
<div class="container">
    <h1>Connexion</h1>

    {% if error %}
        <div class="alert alert-danger">{{ error.messageKey|trans(error.messageData, 'security') }}</div>
    {% endif %}

    {% if certificate %}
        <div class="alert alert-info">
            <strong>Certificat détecté :</strong><br>
            Sujet : {{ certificate.subject_dn }}<br>
            Émetteur : {{ certificate.issuer_dn }}
        </div>
    {% endif %}

    <h2>Veuillez saisir votre identifiant et mot de passe</h2>

    <form method="post" action="{{ path('app_login') }}">
        <div class="form-group">
            <label for="login">Identifiant</label>
            <input type="text" id="login" name="login" class="form-control"
                   value="{{ last_username }}" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>

        <input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">

        <button type="submit" class="btn btn-primary">Connexion</button>
    </form>
</div>
{% endblock %}
```

### 7. Voter pour Permissions par Module (src/Security/ModuleVoter.php)

```php
namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ModuleVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // $subject est le nom du module (string)
        return in_array($attribute, [self::VIEW, self::EDIT])
            && is_string($subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $legacyUser = $user->getLegacyUser();

        // Vérifier que l'utilisateur est actif
        if (!$legacyUser->isActive()) {
            return false;
        }

        // SADM et GADM ont tous les droits
        if ($legacyUser->isGroupAdminOrSuper()) {
            return true;
        }

        $moduleName = $subject; // Nom du module

        return match($attribute) {
            self::VIEW => $legacyUser->canAccess($moduleName),
            self::EDIT => $legacyUser->canEdit($moduleName),
            default => false
        };
    }
}
```

### 8. Utilisation dans les Contrôleurs

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SomeModuleController extends AbstractController
{
    #[Route('/module/view', name: 'module_view')]
    #[IsGranted('view', 'module_name')]
    public function view(): Response
    {
        // Vérification automatique via voter

        // Accès à l'utilisateur Symfony
        $user = $this->getUser(); // Instance de App\Entity\User

        // Accès à l'utilisateur legacy si nécessaire
        $legacyUser = $user->getLegacyUser();

        return $this->render('...');
    }

    #[Route('/module/edit', name: 'module_edit')]
    #[IsGranted('edit', 'module_name')]
    public function edit(): Response
    {
        // ...
    }
}
```

## Plan de Migration

### Phase 1 : Préparation (Sans Modification du Code Legacy)

1. Installer Symfony Security : `composer require symfony/security-bundle`
2. Créer la configuration `security.yaml`
3. Créer l'entité `App\Entity\User` qui wraps `S2lowLegacy\Class\User`
4. Créer le `S2lowUserProvider`
5. Ajouter méthodes manquantes à `UserSQL` (ex: `findByLoginOrEmail`)

### Phase 2 : Authenticator Custom

1. Créer `CertificateAndCredentialsAuthenticator`
2. Implémenter la logique de récupération du certificat
3. Implémenter la logique d'authentification login/password
4. Implémenter la gestion du nonce
5. Tester avec certificat + credentials valides

### Phase 3 : Interface Utilisateur

1. Créer `SecurityController` avec routes `/login` et `/logout`
2. Créer template `login.html.twig`
3. **Modifier la politique** : TOUJOURS afficher le formulaire login
4. Supprimer ou adapter `public.ssl/login.php` et `public.ssl/ident.php`

### Phase 4 : Permissions et Voteurs

1. Créer `ModuleVoter`
2. Créer d'autres voters si nécessaire (ex: `AuthorityVoter`, `UserVoter`)
3. Remplacer les appels `$user->canAccess()` par `$this->isGranted('view', 'module')`
4. Tester les permissions SADM, GADM, ADM, USER, ARCH

### Phase 5 : Migration Progressive

1. Activer Symfony Security en parallèle du système legacy
2. Rediriger certaines routes vers le nouveau système
3. Monitorer les erreurs
4. Une fois stable, désactiver l'ancien système
5. Supprimer le code legacy d'authentification

### Phase 6 : Nettoyage

1. Supprimer `class/Authentification.php` (optionnel, peut rester pour historique)
2. Nettoyer `class/User.php::authenticate()` (déléguer à Symfony)
3. Supprimer `public.ssl/login.php` et `public.ssl/ident.php`
4. Nettoyer constantes `AUTHENTIFICATION_BY_APACHE` et `AUTHENTIFICATION_BY_FORM`

## Points d'Attention

### Sécurité

1. **Certificats SSL** : Toujours obligatoires (Apache), ne jamais les rendre optionnels
2. **CSRF** : Activer la protection CSRF sur le formulaire de login
3. **Rate Limiting** : Implémenter un rate limiter sur `/login` pour éviter brute-force
4. **Session Timeout** : Configurer une durée de session appropriée
5. **Mots de passe MD5** : Migration automatique vers bcrypt lors de la première connexion

### Performance

1. **Cache des permissions** : Les permissions sont chargées à chaque requête via `initPerms()`
   - Considérer un cache Redis/APCu pour `$user->getRoles()`
2. **Requêtes SQL** : Optimiser `UserSQL` pour éviter N+1 queries

### Compatibilité

1. **Code Legacy** : Maintenir `S2lowLegacy\Class\User` fonctionnel pendant la transition
2. **Sessions** : Migrer progressivement de `$_SESSION['id_login']` vers sessions Symfony
3. **Helpers** : Adapter `Helpers::returnAndExit()` pour utiliser exceptions Symfony

### Tests

1. Créer tests pour `CertificateAndCredentialsAuthenticator`
2. Tester avec certificat unique (ancien comportement)
3. Tester avec certificat partagé + login/password
4. Tester nonce
5. Tester permissions par rôle
6. Tester permissions par module

## Exemple de Tests Unitaires

```php
namespace App\Tests\Security;

use App\Security\CertificateAndCredentialsAuthenticator;
use PHPUnit\Framework\TestCase;

class CertificateAndCredentialsAuthenticatorTest extends TestCase
{
    public function testAuthenticateWithValidCertificateAndCredentials(): void
    {
        // Mock dependencies
        $userProvider = $this->createMock(S2lowUserProvider::class);
        $httpsConnexion = $this->createMock(\S2lowLegacy\Class\HttpsConnexion::class);
        $nounceSQL = $this->createMock(\S2lowLegacy\Model\NounceSQL::class);

        // Setup mock behavior
        $httpsConnexion->method('hasNonceParameters')->willReturn(false);
        $httpsConnexion->method('getCertificateInfo')->willReturn([
            'certificate_hash' => 'abc123',
            'subject_dn' => '/CN=Test User',
            'issuer_dn' => '/CN=Test CA'
        ]);

        // Create request with POST credentials
        $request = Request::create('/login', 'POST', [
            'login' => 'testuser',
            'password' => 'testpass'
        ]);

        $authenticator = new CertificateAndCredentialsAuthenticator(
            $userProvider,
            $httpsConnexion,
            $nounceSQL
        );

        $passport = $authenticator->authenticate($request);

        $this->assertInstanceOf(Passport::class, $passport);
    }
}
```

## Questions à Clarifier Avant Implémentation

1. **Rétrocompatibilité** : Faut-il maintenir `public.ssl/login.php` pour une transition douce ? Reponse : Oui
2. **Certificat unique** : Doit-on VRAIMENT forcer login/password même si un seul user utilise le certificat ? Reponse : Non
3. **Remember Me** : Activer la fonctionnalité "Se souvenir de moi" ? Reponse : Jusqua fermeture du naviagteur seulement
4. **2FA** : Envisager une authentification à deux facteurs ? Reponse cela viendra par la suite
5. **API** : Y a-t-il des endpoints API nécessitant une authentification différente (tokens JWT) ? Reponse : il y a des endpoint API. POur le moment ils fournisse des certificats.
6. **Logging** : Quelle stratégie de logging pour les tentatives de connexion ? Login / Mot de passe

## Ressources Utiles

- [Symfony Security Documentation](https://symfony.com/doc/6.4/security.html)
- [Custom Authenticators](https://symfony.com/doc/6.4/security/custom_authenticator.html)
- [Voters](https://symfony.com/doc/6.4/security/voters.html)
- [Password Hashers](https://symfony.com/doc/6.4/security/passwords.html)

---

**Prêt pour implémentation** : Ce document contient toutes les informations nécessaires pour migrer l'authentification vers Symfony Security tout en conservant la compatibilité avec l'existant.
