<?php

class AdminUtilitiesCertificateController extends Controller
{
    public const SESSION_KEY = 'last_certificate_analyse';

    public function testAction()
    {
        $this->verifSuperAdmin();
        $this->title = "Test de certificat";

        $session_info = $this->getEnvironnement()->session()->get(self::SESSION_KEY);
        if ($session_info) {
            $this->certificate_info = $session_info;
        } else {
            $this->certificate_info = false;
        }
    }

    /**
     * @throws RedirectException
     */
    public function doTestAction()
    {
        $files = $this->getFiles();
        if (empty($files['certificat']['tmp_name'])) {
            $this->redirect("/admin/utilities/test-certificate.php", "Il faut fournir un fichier");
        }

        $x509certificate = new X509Certificate();

        try {
            $certificate_info['certificate_info'] = $x509certificate->getInfo(file_get_contents($files['certificat']['tmp_name']));
        } catch (Exception $e) {
            $this->redirect(
                "/admin/utilities/test-certificate.php",
                "Erreur lors de l'analyse du certificat : " . $e->getMessage()
            );
        }

        $rgsCertificate = new RgsCertificate(
            $this->getObjectInstancier()->get('openssl_path'),
            $this->getObjectInstancier()->get('rgs_validca_path')
        );
        $clientCertChain = null;
        if (isset($files['certificat']['tmp_chaine']) && file_exists(file_get_contents($files['certificat']['tmp_chaine']))) {
            $clientCertChain = file_get_contents($files['certificat']['tmp_chaine']);
        }

        $certificate_info['is_rgs'] = $rgsCertificate->isRgsCertificate(file_get_contents($files['certificat']['tmp_name']), $clientCertChain);

        $rgsCertificate = new RgsCertificate(
            $this->getObjectInstancier()->get('openssl_path'),
            $this->getObjectInstancier()->get('extended_validca_path')
        );
        $certificate_info['is_extended'] = $rgsCertificate->isRgsCertificate(file_get_contents($files['certificat']['tmp_name']), $clientCertChain);

        $userSQL = $this->getObjectInstancier()->get(UserSQL::class);
        $certificate_info['nb_users'] = $userSQL->getNbUserWithMyCertificate($certificate_info['certificate_info']['certificate_hash']);
        if ($certificate_info['nb_users']) {
            $certificate_info['user_id'] = $userSQL->getIdListFromCertificateInfo($certificate_info['certificate_info']['certificate_hash'])[0];
        } else {
            $certificate_info['user_id'] = false;
        }

        $this->getEnvironnement()->session()->set(self::SESSION_KEY, $certificate_info);
        $this->redirect("/admin/utilities/test-certificate.php");
    }
}
