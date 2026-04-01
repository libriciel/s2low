<?php

declare(strict_types=1);

namespace PHPUnit\controller;

use S2lowLegacy\Model\UserSQL;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 *
 */
class AuthenticationNounceControllerTest extends WebTestCase
{
    public function testGetNounce(): void
    {
        $client = static::createClient();

        $user = static::getContainer()->get(UserSQL::class)->getUserById(51);
        $certif = [
            'SSL_CLIENT_VERIFY' => 'SUCCESS',
            'SSL_CLIENT_S_DN' => 'subject_dn',
            'SSL_CLIENT_I_DN' => 'issuer_dn',
            'SSL_CLIENT_CERT' => "-----BEGIN CERTIFICATE-----
MIIC5DCCAk0CAgCaMA0GCSqGSIb3DQEBBQUAMGwxCzAJBgNVBAYTAkZSMQ8wDQYD
VQQIEwZGcmFuY2UxDTALBgNVBAcTBEx5b24xETAPBgNVBAoTCFNpZ21hbGlzMSow
KAYDVQQDEyFhdXRvcml0ZSBkZXZlbG9wcGVtZW50IHNpdGUgczJsb3cwHhcNMTUw
MjI0MDcxNTEwWhcNMjUwMjIxMDcxNTEwWjCBgzELMAkGA1UEBhMCRlIxDzANBgNV
BAgMBkZyYW5jZTENMAsGA1UEBwwETHlvbjERMA8GA1UECgwIU2lnbWFsaXMxDjAM
BgNVBAsMBVMybG93MQ4wDAYDVQQDDAVVc2VyMTEhMB8GCSqGSIb3DQEJARYSdXNl
cjFAc2lnbWFsaXMuY29tMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA
1OLHZasbElNMeMB70SJjxJx/42533XF5fpqrkkpozdgWz/HQIvhDWfNBjXhMGzmK
tzCUhLMFQ9lEpCeh6rJgLEmDPeDdhY8mlbgM94xr3a3QKE/XLvIIuN/g0al1674m
U9LqFEWVyF5NiC3m9b8NgYwyeiyArJnIwz34Z9SZRcU7v4Lp35oWBbeRQMPH/YQm
xouBNltEDBDSbjIOgxBPmbtaqTmU+3WcsbNYjWAg8CT2pcKyPx5mZ8PSLdXmgrRS
Etu32dWv0FP6Ed8YtupDSG5OWmfUvKR/elp2cYn9bnx/4EUAotTJGhHUJjlDdaqk
sHwZ+e9W1klKS0H5qcs8CwIDAQABMA0GCSqGSIb3DQEBBQUAA4GBABs6IQntY/51
k4IqRZW6ngVsDcUnL04mzTrr/RGB1Y/U4eyERJyFMMFvjIEC42ClJaVWOx5R0SBk
8SriNEesjQv50OcHsnM4WePHBOrIyuhjSHPl6ovzptPe9BmLaMGQHErFoMp5w9VV
IdykrHXLsjQAowE3JSvL52KOeqkzyTin
-----END CERTIFICATE-----",
            'PHP_AUTH_USER' => 'login',
            'PHP_AUTH_PW' => 'password',
        ];

        $client->request(
            'GET',
            '/api/get-nounce.php',
            [],
            [],
            $certif
        );

        $outputContent = $client->getResponse()->getContent();
        $jsonOutput = json_decode($outputContent, true, 512, JSON_THROW_ON_ERROR);

        static::assertSame(['nounce'], array_keys($jsonOutput));
    }
}
