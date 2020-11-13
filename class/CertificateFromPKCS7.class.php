<?php

class CertificateFromPKCS7{
    /** @var string  */
    private $pathOnDrive;
    private $authorized_ca_path;
    /** @var OpenSslWrapper */
    private $openSslWrapper;

    public function __construct($authorized_ca_path,$openSslWrapper)
    {
        $this->pathOnDrive = sys_get_temp_dir() . "/slow_signature_".mt_rand(0,mt_getrandmax());
        $this->authorized_ca_path = $authorized_ca_path;
        $this->openSslWrapper = $openSslWrapper;
    }

    public function getPathOnDrive(){
        return $this->pathOnDrive;
    }

    public function setCertificateContent($certificate){
        $result = file_put_contents($this->pathOnDrive, $certificate);
        if ($result === false){
            throw new Exception("Impossible d'écrire le certificat dans {$this->pathOnDrive}");
        }
    }

    public function check(){
        list($verifyCmd, $out, $ret, $result) = $this->openSslWrapper->verifyCertificate(
            $this->pathOnDrive,
            $this->authorized_ca_path   // TODO = add to construct
        );

        /*if ($ret != 0) {            # ??? Le retour peut-il être = 0 quand il y a une erreur ????
			throw new Exception("Erreur #$ret lors de la verification du certificat (commande : $verifyCmd) (result: $result)");
		}*/

        var_dump($out);
        //TODO C'est n'imp. Il devrait y avoir toutes les lignes dans le out
        $nonBlockingVerifyErrors = [2,  # unable to get issuer certificate
            3,  # unable to get certificate CRL
            18, # self signed certificate
            19, # self signed certificate in certificate chain
            20, # unable to get local issuer certificate
            21, # unable to verify the first certificate
        ];

        $nonBlockingErrorThrown = false;
        foreach ($out as $line) {
            if(preg_match("/error ([0123456789]+) at ([0123456789])+ depth lookup:(.*)/",$line,$matches)){
                var_dump($matches);
                if(!in_array($matches[1],$nonBlockingVerifyErrors)){
                    throw new Exception("Erreur #{$matches[1]} lors de la verification du certificat (commande : $verifyCmd) (result: $result)");
                } else {
                    $nonBlockingErrorThrown = true;
                }
            }
            /*if (stripos($line, 'certificate revoked') !== false) {      # Pourquoi pourrait-on arriver ici ?
                throw new Exception("Erreur #$ret lors de la verification du certificat (commande : $verifyCmd) (result: $result)");
            }*/
        }

        if($nonBlockingErrorThrown){
            if(!$this->openSslWrapper->isDateValid($this->pathOnDrive)){
                throw new Exception("Erreur lors de la verification du certificat (commande : ) (result: )");   //TODO : modify
            }
        }

        return true;
    }
}