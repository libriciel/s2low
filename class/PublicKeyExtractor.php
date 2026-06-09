<?php

namespace S2lowLegacy\Class;

class PublicKeyExtractor
{
    public function getPublicKeyHashFromCertificatePath($certificatePath): string
    {
        $certContent = file_get_contents($certificatePath);
        $publicKey = openssl_pkey_get_public($certContent);
        if ($publicKey === false) {
            return "";
        }
        $details = openssl_pkey_get_details($publicKey);
        $pubKeyPem = $details['key'];

        $lines = explode("\n", trim($pubKeyPem));
        if (str_contains($lines[0], 'BEGIN PUBLIC KEY')) {
            array_shift($lines);
        }
        if (str_contains($lines[count($lines) - 1], 'END PUBLIC KEY')) {
            array_pop($lines);
        }
        $der = base64_decode(implode("", $lines));

        return base64_encode(hash('sha256', $der, true));
    }
}
