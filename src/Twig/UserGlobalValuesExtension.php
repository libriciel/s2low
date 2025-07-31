<?php

namespace S2low\Twig;

use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Lib\X509Certificate;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class UserGlobalValuesExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly Initialisation $initialisation
    ) {
    }

    public function getGlobals(): array
    {
        try {
            $profileData = $this->initialisation->doInit();
            $userData = $profileData->userInfo;
        } catch (\Exception $e) {
            return [];
        }

        $userDto = $this->getUserDto($userData);

        return [
            'user_info' => $userDto,
            'nb_day_before_certificate_expire' => $this->getNbDaysBeforeCertificatExpire($userData),
        ];
    }

    private function getUserDto($userInfo): array
    {
        return [
            'id' => $userInfo['id'],
            'lastname' => $userInfo['name'],
            'name' => $userInfo['givenname'],
            'role_str' => $userInfo['role_str'],
            'role' => $userInfo['role'],
            'authority_id' => $userInfo['authority_id'],
            'nb_user_with_my_certificate' => $userInfo['nb_user_with_my_certificate'],
        ];
    }

    // TODO : NE PAS MERGE  Code, il faudra faire une classe pour gerer ca
    private function getNbDaysBeforeCertificatExpire($userInfo): int
    {
        $x509Certificate = new X509Certificate();
        $expiration_time =  strtotime($x509Certificate->getExpirationDate($userInfo['certificate']));
        return floor(($expiration_time - time()) / 86400);
    }
}
