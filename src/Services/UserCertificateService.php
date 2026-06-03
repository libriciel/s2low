<?php

namespace S2low\Services;

use Exception;
use Psr\Clock\ClockInterface;
use S2low\Exceptions\CertificateException;
use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\UserSQL;

class UserCertificateService
{
    private const SECONDS_PER_DAY = 24 * 60 * 60;

    public function __construct(
        private readonly UserSQL $userSQL,
        private readonly X509Certificate $x509Certificate,
        private readonly ClockInterface $clock
    ) {
    }

    public function getNbDaysBeforeUserCertificatExpire(int $userId): int
    {
        $userCertificate = $this->userSQL->getUserCertificate($userId);
        try {
            $certificateExpirationDate = $this->x509Certificate->getExpirationDate($userCertificate);
        } catch (Exception $e) {
            throw new CertificateException($e->getMessage());
        }

        $expiration_time =  strtotime($certificateExpirationDate);
        return floor(($expiration_time - $this->clock->now()->getTimestamp()) / self::SECONDS_PER_DAY);
    }
}
