<?php

class SignatureFromPKCS7{

    private string $pathOnDrive;

    public function __construct()
    {
        $this->pathOnDrive = sys_get_temp_dir() . "/slow_signature_".mt_rand(0,mt_getrandmax());
    }

    public function getPathOnDrive(){
        return $this->pathOnDrive;
    }

    public function setSignatureContent($signature){
        $result = file_put_contents($this->pathOnDrive, $signature);
        if ($result === false){
            throw new Exception("Impossible d'écrire la signature dans {$this->pathOnDrive}");
        }
    }

    public function getCertificate(){
        $extractCmd = "openssl pkcs7 -in " . $this->pathOnDrive . " -print_certs | openssl x509";

        exec($extractCmd, $output, $ret);

        if ( $ret ) {
            throw new Exception("Erreur d'extraction du certificat : echec de la commande $extractCmd");
        }

        $cert = implode("\n",$output);
        $cert.="\n";

        return $cert;
    }
}