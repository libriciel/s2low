<?php

declare(strict_types=1);

namespace PHPUnit\controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthenticationNounceControllerFailTest extends WebTestCase
{
    public function testGetNounceWithoutAuthFails(): void
    {
        $client = static::createClient();
        $certif = [
            'SSL_CLIENT_VERIFY' => 'SUCCESS',
            'SSL_CLIENT_S_DN' => 'subject_dn',
            'SSL_CLIENT_I_DN' => 'issuer_dn',
            'SSL_CLIENT_CERT' => "-----BEGIN CERTIFICATE-----
MIIF1jCCA76gAwIBAgIIVzbCgX11r5YwDQYJKoZIhvcNAQELBQAwRTELMAkGA1UE
BhMCRlIxEjAQBgNVBAoMCUxpYnJpY2llbDEiMCAGA1UEAwwZQUMgTGlicmljaWVs
IFBlcnNvbm5lbCBHMjAeFw0yNTAzMjUwOTI0NTFaFw0yODAzMjQwOTI0NTFaMIHT
MQswCQYDVQQGEwJGUjEVMBMGA1UECAwMMzQgLSBIZXJhdWx0MRQwEgYDVQQHDAtN
b250cGVsbGllcjESMBAGA1UECgwJbGlicmljaWVsMScwJQYDVQQLDB5jaGFybGVz
LmR1dGhlaWxAbGlicmljaWVsLmNvb3AxNzA1BgNVBAMMLmNoYXJsZXMgUzJsb3cg
LSBjaGFybGVzLmR1dGhlaWxAbGlicmljaWVsLmNvb3AxITAfBgkqhkiG9w0BCQEW
EmZha2UtY2VydEBzMmxvdy5mcjCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoC
ggEBAJo3gTXSc91YYNH0JB39XZxGsSA5mS0zDj7lL12KUriiovzFboL/Kl9fHmu9
CaXs1vfN4KwZ7grijW8KNrv6He4N9iNz2Wn3OLyi2Pa5i74VUDWdg6ssmos7SjuM
9LP+2OpMCx3p4O+N7EzP1ivWhc8lC+wAKrYRYtXExraExOFAT+Fkr4XsyisPhZ/M
7MEHAevRGJH7wGWWwd1x4wf9hk/ZuxPuhavdfxfFkksZPmEUg6VcWytMC4fW5iUa
I9fZzvhtes/KCrjzf4i/KtsqKLR4Y4zfsSHabj90ydfGdxFhb3zz/lks3Lcn0SRi
apvfsOc41NmSU9974USDIfEOKiMCAwEAAaOCATkwggE1MAwGA1UdEwEB/wQCMAAw
HwYDVR0jBBgwFoAU1XWSDfBlWv7xu1INsuaZnVe/c8AwOAYIKwYBBQUHAQEELDAq
MCgGCCsGAQUFBzABhhxodHRwOi8vcGtpLmxpYnJpY2llbC5mci9vY3NwMB0GA1Ud
EQQWMBSBEmZha2UtY2VydEBzMmxvdy5mcjA0BgNVHSUELTArBggrBgEFBQcDAgYI
KwYBBQUHAwQGCisGAQQBgjcKAwwGCSqGSIb3LwEBBTBGBgNVHR8EPzA9MDugOaA3
hjVodHRwOi8vcGtpLmxpYnJpY2llbC5mci9hYy1saWJyaWNpZWwtcGVyc29ubmVs
LWcyLmNybDAdBgNVHQ4EFgQU3B0aTVKR2hRnvuo2/GZzeD30pxEwDgYDVR0PAQH/
BAQDAgXgMA0GCSqGSIb3DQEBCwUAA4ICAQAhoPmnOIxBo39dQypmA4o7PYwSn6j+
/iES5NgVOuXYzIG2A79AGMgSogMcdhk+ZycS1H8r7pPjY+W1Nzh/CB+7rYYt8xq1
hrGwfwcPZutRm+/2XQPNqykIFLx9JOKv3Ml5WvRECtktvXA42R+HpzJoLaQEX8Ga
/cefMe9KR0Wp7E1ozzgctUqzrGf2UYWUMbngbHs9VwYMqin46jXJNmPa3uzqZXHK
E+ZFiUpJM0pXs2YRKz+H5N0ubv1ZdrH5JLMZN2iTS2lFJGjdZbmn4i7iEIHuHdtz
bBQkZC5QRbbS7vOSTd386DVDELAuYJfaa/EjKdxJPaOrrNm4hkX2l6w7b4JYsIbq
rAee+aQktxFmbPUCJj8/BOx6+4ZQ2pf9hSOlShA0qzHKurIBg/QoljvXxieHN/NJ
yDFjU9TDnx7PosEUA+jAukiZ/px5jOj9TA9mA81Z93GvECQqH/Qra67Rux9oCgIp
gQDUuZ1JxqxeiMHYpjW8qcqXbTIy74p33/d6/MpCNsYtRQjbFsgjbvObeGevEwLu
gnjxKkg9bBY1ZseYiWKEWyJAN6nNFx/nLgD/AjvrZ35zyS+y9ozEUjanzimh5mmt
53/khebINUWsNLg/uc/BEipEoiq8ossEsjOWPEsCBdBn/4rLzA2Rq9x7KV7QakGm
NOquoycVBQ9vLg==
-----END CERTIFICATE-----",
            ];

        $client->request(
            'GET',
            '/api/get-nounce.php',
            [],
            [],
            $certif
        );

        $response = $client->getResponse();

        static::assertSame(401, $response->getStatusCode());
        static::assertStringContainsString('Www-Authenticate', (string)$response->headers);
    }
}
