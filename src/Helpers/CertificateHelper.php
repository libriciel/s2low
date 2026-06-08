<?php

namespace S2low\Helpers;

class CertificateHelper
{
    private string $lastError = '';

    /**
     * @return string
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * @param string $path
     * @return array|false
     */
    public function getAuthorizedCACerts(string $path = EXTENDED_VALIDCA_PATH): array|false
    {
        $certs = array();

        if (is_dir($path)) {
            if (! $files = scandir($path)) {
                return false;
            }

            foreach ($files as $file) {
                $file = $path . "/" . $file;

                if (is_file($file) && ! is_link($file)) {
                    if (! $cert = @file_get_contents($file)) {
                        $this->lastError .= "Certficate file error in validca:" . $file . "\n";
                        continue;
                    }

                    if ($x509 = openssl_x509_parse($cert)) {
                        $certs[] = $x509;
                    }
                }
            }
        }

        return $certs;
    }
}
