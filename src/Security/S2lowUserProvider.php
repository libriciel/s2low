<?php

namespace S2low\Security;

use S2low\Entity\User;
use S2lowLegacy\Class\User as LegacyUser;
use S2lowLegacy\Model\UserSQL;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

/**
 * UserProvider personnalisé pour S2low
 * Charge les utilisateurs depuis la base de données legacy
 */
class S2lowUserProvider implements UserProviderInterface
{
    public function __construct(
        private UserSQL $userSQL
    ) {}

    /**
     * Charge un utilisateur par son identifiant (login ou email)
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Recherche par login ou email
        $userData = $this->findByLoginOrEmail($identifier);

        if (!$userData) {
            throw new UserNotFoundException(sprintf('Utilisateur "%s" introuvable', $identifier));
        }

        // Créer et initialiser l'utilisateur legacy
        $legacyUser = new LegacyUser($userData['id']);
        if (!$legacyUser->init()) {
            throw new UserNotFoundException('Erreur lors de l\'initialisation de l\'utilisateur');
        }

        return new User($legacyUser);
    }

    /**
     * Charge un utilisateur par hash de certificat et credentials
     * Cette méthode est utilisée pour l'authentification combinée certificat + login/password
     */
    public function loadUserByCertificateHashAndCredentials(
        string $certificateHash,
        ?string $login,
        ?string $password
    ): UserInterface {
        // Si login et password fournis
        if ($login && $password) {
            $users = $this->userSQL->getIdsAndPasswordsFromConnexionInfo(
                $certificateHash,
                '',  // certificate_rgs_2_etoiles vide (déprécié)
                $login
            );

            // Vérifier le mot de passe pour chaque utilisateur trouvé
            foreach ($users as $userData) {
                $passwordHandler = new \S2lowLegacy\Class\PasswordHandler($this->userSQL);
                if ($passwordHandler->passwordMatchesHash($password, $userData['password'], $userData['id'])) {
                    // Mot de passe correct, charger l'utilisateur complet
                    $legacyUser = new LegacyUser($userData['id']);
                    if (!$legacyUser->init()) {
                        throw new UserNotFoundException('Erreur lors de l\'initialisation de l\'utilisateur');
                    }
                    return new User($legacyUser);
                }
            }

            // Aucun utilisateur avec ce login/password
            throw new UserNotFoundException('Login ou mot de passe incorrect');
        }

        // Sans credentials : on cherche si un seul utilisateur a ce certificat
        $userIds = $this->userSQL->getIdListFromCertificateInfo($certificateHash);

        if (count($userIds) === 0) {
            throw new UserNotFoundException('Aucun utilisateur associé à ce certificat');
        }

        if (count($userIds) === 1) {
            // Un seul utilisateur, on le charge
            $legacyUser = new LegacyUser($userIds[0]);
            if (!$legacyUser->init()) {
                throw new UserNotFoundException('Erreur lors de l\'initialisation de l\'utilisateur');
            }
            return new User($legacyUser);
        }

        // Plusieurs utilisateurs partagent ce certificat : login/password requis
        throw new UserNotFoundException('Plusieurs utilisateurs utilisent ce certificat. Login et mot de passe requis.');
    }

    /**
     * Rafraîchit l'utilisateur depuis la base de données
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException(
                sprintf('Les instances de "%s" ne sont pas supportées.', get_class($user))
            );
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    /**
     * Indique si ce provider supporte la classe d'utilisateur donnée
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    /**
     * Recherche un utilisateur par login ou email
     */
    private function findByLoginOrEmail(string $identifier): ?array
    {
        // Recherche par login
        $sql = "SELECT id FROM users WHERE login = ? AND status = ?";
        $result = $this->userSQL->queryOne($sql, [$identifier, UserSQL::STATUS_ACTIVE]);

        if ($result) {
            return $this->userSQL->getInfo($result['id']);
        }

        // Recherche par email si pas trouvé par login
        $sql = "SELECT id FROM users WHERE email = ? AND status = ?";
        $result = $this->userSQL->queryOne($sql, [$identifier, UserSQL::STATUS_ACTIVE]);

        if ($result) {
            return $this->userSQL->getInfo($result['id']);
        }

        return null;
    }
}
