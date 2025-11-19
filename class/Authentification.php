<?php

namespace S2lowLegacy\Class;

use Exception;
use S2low\Security\LegacyAuthenticationBridge;
use S2lowLegacy\Lib\Environnement;

class Authentification
{
    private Environnement $environnement;
    private ?LegacyAuthenticationBridge $authBridge;

    public function __construct(
        Environnement $environnement,
        ?LegacyAuthenticationBridge $authBridge = null
    ) {
        $this->environnement = $environnement;
        $this->authBridge = $authBridge;
    }

    /**
     * Authentifie l'utilisateur via Symfony Security.
     *
     * @return int L'ID de l'utilisateur authentifié
     * @throws Exception Si l'utilisateur n'est pas authentifié
     */
    public function authenticate(): int
    {
        if ($this->authBridge && $this->authBridge->isAuthenticated()) {
            $userId = $this->authBridge->getAuthenticatedUserId();
            // Synchroniser avec la session legacy pour compatibilité avec le code existant
            $this->environnement->session()->set('id_login', $userId);
            return $userId;
        }

        throw new Exception("La connexion n'a pas pu être établie");
    }
}
