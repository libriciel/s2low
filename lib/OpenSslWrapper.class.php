<?php


class OpenSslWrapper
{
    /**
     * @param $certificate_path
     * @param $out
     * @param $ret
     * @return array
     */
    public function verifyCertificate($certificate_path,$authorized_ca_path): array
    {
        $verifyCmd = "openssl verify -CApath $authorized_ca_path -crl_check $certificate_path";
        exec($verifyCmd, $out, $ret);

        $result = implode("\n", $out);
        return array($verifyCmd, $out, $ret, $result);
    }

    public function isDateValid($certificate_path)
    {
        $x509_data = openssl_x509_parse(file_get_contents("example.crt"));
        $validFrom = date_create_from_format('ymdHise', $x509_data['validFrom'])->format('c');
        $validTo = date_create_from_format('ymdHise', $x509_data['validTo'])->format('c');

        $today = getdate();

        return ( $validFrom < $today ) && ( $today < $validTo );
    }
}