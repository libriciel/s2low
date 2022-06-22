<?php

class PKCS12
{
    public function getAll($p12_file_path, $p12_password)
    {
        if (! file_exists($p12_file_path)) {
            throw new Exception("Le fichier $p12_file_path n'existe pas");
        }
        $pkcs12 = file_get_contents($p12_file_path);

        $result = openssl_pkcs12_read($pkcs12, $certs, $p12_password);

        if (! $result) {
            throw new Exception("Impossible de lire le certificat PKCS#12");
        }
        openssl_pkey_export($certs['pkey'], $pkey, $p12_password);
        return array('cert' => $certs['cert'],'pkey' => $pkey);
    }

    public function getX509CertificateContent($p12_file_path, $p12_password)
    {
        $all_info = $this->getAll($p12_file_path, $p12_password);
        return $all_info['cert'];
    }
}
