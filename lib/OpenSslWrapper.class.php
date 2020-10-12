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
}