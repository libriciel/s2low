<?php


class VerifyPemCertificate
{
    # extracted from https://github.com/openssl
    # Mise en correspondance de  openssl/crypto/x509/x509_txt.c
    # et https://docs.huihoo.com/doxygen/openssl/1.0.1c/crypto_2x509_2x509__vfy_8h.html
    const CERTIFICATE_CHAIN_ERRORS = array (
        2,  # unable to get issuer certificate
        3,  # unable to get certificate CRL
        18, # self signed certificate
        19, # self signed certificate in certificate chain
        20, # unable to get local issuer certificate
        21, # unable to verify the first certificate
    );
    /** @var string  */
    private $authorized_ca_path;
    /**
     * @var \S2low\Services\ExtractIssuerHashCommand
     */
    private $extractIssuerHashCommand;
    /**
     * @var \S2low\Services\ExtractCertificateSNCommand
     */
    private $extractCertificateSN;
    /**
     * @var \S2low\Services\CheckSnInCRLCommand
     */
    private $checkSnInCRL;
    /**
     * @var \S2low\Services\OpensslVerifyCommand
     */
    private $opensslVerify;

    public function __construct(
        string                                      $authorized_ca_path,
        \S2low\Services\ExtractIssuerHashCommand    $extractIssuerHashCommand,
        \S2low\Services\ExtractCertificateSNCommand $extractCertificateSN,
        \S2low\Services\CheckSnInCRLCommand         $checkSnInCRL,
        \S2low\Services\OpensslVerifyCommand $opensslVerify
    ){
        $this->authorized_ca_path = $authorized_ca_path;
        $this->extractIssuerHashCommand = $extractIssuerHashCommand;
        $this->extractCertificateSN = $extractCertificateSN;
        $this->checkSnInCRL = $checkSnInCRL;
        $this->opensslVerify = $opensslVerify;
    }

    /**
     * @throws Exception
     */
    public function checkCertificateWithOpenSSL($certificate_path, array $filteredErrors = [], string $timestamp = null ): bool
    {
        $this->checkForCrlRevocation($certificate_path);
        $this->opensslVerify->verify($certificate_path,$filteredErrors,$timestamp);
        return true;
    }

    /**
     * @param string $file
     * @return void
     * @throws \Exception
     */
    protected function checkForCrlRevocation(string $file) : void
    {
        $file_r0_name = $this->extractIssuerHashCommand->extract($file);
        $file_r0 = $this->authorized_ca_path . "/$file_r0_name.r0";
        if (file_exists($file_r0)) {
            // 1) extraire le SN du certificat
            $serialNumber =$this->extractCertificateSN->extract($file);
            // 2) vérifier que ce SN n'est pas présent dans la CRL (Pour l'instant, la date n'est pas prise en compte)
            // On ne vérifie pas
            // 1) la date
            // 2) si la CRL garde bien les certificats expirés ( extension 2.5.29.60 )
            $this->checkSnInCRL->check($file_r0,$serialNumber);
        }
    }
}