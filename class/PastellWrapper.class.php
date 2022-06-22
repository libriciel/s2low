<?php

class PastellWrapper
{
    const ACTES_FLUX_ID_DEFAULT = 'actes-generique';
    const HELIOS_FLUX_ID_DEFAULT = 'helios-generique';

    private $lastError;

    private $pastellProperties;

    private $curlWrapperFactory;

    private $s2lowLogger;

    public function __construct(
        PastellProperties $pastellProperties,
        CurlWrapperFactory $curlWrapperFactory,
        S2lowLogger $s2lowLogger
    ) {
        $this->pastellProperties = $pastellProperties;
        $this->curlWrapperFactory = $curlWrapperFactory;
        $this->s2lowLogger = $s2lowLogger;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * @param $url
     * @param array $postData
     * @param array $postFile
     * @return bool|mixed
     * @throws Exception
     */
    private function callAPI($url, array $postData = array(), $postFile = array(), $http_verb = "")
    {

        if (! $url) {
            $this->s2lowLogger->alert("Pastell n'est pas configuré !");
            return false;
        }

        $curl_wrapper = $this->curlWrapperFactory->getNewInstance();
        $curl_wrapper->dontVerifySSLCACert();
        $curl_wrapper->httpAuthentication($this->pastellProperties->login, $this->pastellProperties->password);
        $curl_wrapper->setTimeout(300, 3600);

        if ($http_verb != 'PATCH') {
            foreach ($postData as $name => $value) {
                $curl_wrapper->addPostData($name, $value);
            }
        } else {
            $curl_wrapper->setProperties(CURLOPT_POSTFIELDS, http_build_query($postData));
        }
        foreach ($postFile as $field => $file_info) {
            $curl_wrapper->addPostFile($field, $file_info[0], $file_info[1]);
        }

        if ($http_verb == 'PATCH') {
            $curl_wrapper->setPatch();
        }

        $this->s2lowLogger->debug("Pastell request: " . $this->pastellProperties->url . "/" . $url . " with post data :" . json_encode($postData));
        $raw_data = $curl_wrapper->get($this->pastellProperties->url . "/" . $url);
        $this->s2lowLogger->debug("Pastell response: $raw_data");
        if (!$raw_data) {
            throw new Exception($curl_wrapper->getLastError());
        }

        $data = json_decode($raw_data, true);
        if (! $data) {
            throw new Exception("Impossible de décoder les données reçu : $raw_data");
        }

        if (isset($data['status']) && $data['status'] == 'error') {
            throw new Exception("Message de Pastell : " . $data['error-message']);
        }
        return $data;
    }


    /**
     * @return bool
     * @throws Exception
     */
    public function testConnexion()
    {
        $data = $this->callAPI("list-entite.php");
        foreach ($data as $entite) {
            if ($entite['id_e'] == $this->pastellProperties->id_e) {
                return true;
            }
        }
        $this->lastError = "L'entité Pastell « {$this->pastellProperties->id_e} » n'est pas autorisé pour l'utilisateur « {$this->pastellProperties->login} ».";
        return false;
    }


    /**
     * @param $transactionInfo
     * @return bool
     * @throws Exception
     */
    public function createActes($transactionInfo)
    {

        $flux_id = $this->pastellProperties->actes_flux_id ?: self::ACTES_FLUX_ID_DEFAULT;

        $result = $this->callAPI("create-document.php?id_e={$this->pastellProperties->id_e}&type=$flux_id");
        if (empty($result['id_d'])) {
            throw new Exception("Impossible de créer le document sur Pastell");
        }

        $id_d = $result['id_d'];
        $info = array(  'id_e' => $this->pastellProperties->id_e,
                        'id_d' => $id_d,
                        'acte_nature' => $transactionInfo['nature_code'],
                        'numero_de_lacte' => $transactionInfo['number'],
                        'objet' => $transactionInfo['subject'],
                        'date_de_lacte' => date("Y-m-d", strtotime($transactionInfo['decision_date'])),
                        'classification' => $transactionInfo['classification'],
                        'envoi_sae' => 1,
                        'has_bordereau' => 1
        );

        $this->callAPI("modif-document.php", $info);
        return $id_d;
    }

    /**
     * @param $transactionInfo
     * @return bool
     * @throws Exception
     */
    public function createHelios($transactionInfo)
    {
        $flux_id = $this->pastellProperties->helios_flux_id ?: self::HELIOS_FLUX_ID_DEFAULT;

        $result = $this->callAPI("create-document.php?id_e={$this->pastellProperties->id_e}&type=$flux_id");
        if (empty($result['id_d'])) {
            throw new Exception("Impossible de créer le document sur Pastell");
        }
        $id_d = $result['id_d'];
        $info = array(  'id_e' => $this->pastellProperties->id_e,
                        'id_d' => $id_d,
                        'objet' => $transactionInfo['filename'],
                        'tedetis_transaction_id' => $transactionInfo['id'],
                        'envoi_sae' => 1,
        );

        $this->callAPI("modif-document.php", $info);
        return $id_d;
    }

    /**
     * @param $id_d
     * @param $date
     * @return bool|mixed
     * @throws Exception
     */
    public function setDatePostage($id_d, $date)
    {
        return $this->callAPI(
            "modif-document.php",
            array(
                    'id_e' => $this->pastellProperties->id_e,
                    'id_d' => $id_d,
                    'date_tdt_postage' => $date
                )
        );
    }

    /**
     * @param $id_d
     * @param $signature_file_path
     * @return bool|mixed
     * @throws Exception
     */
    public function postSignature($id_d, $signature_file_path)
    {
        return $this->postFile($id_d, 'signature', $signature_file_path);
    }

    /**
     * @param $id_d
     * @param $field
     * @param $file_path
     * @param bool $file_orig_name
     * @return bool|mixed
     * @throws Exception
     */
    public function postFile($id_d, $field, $file_path, $file_orig_name = false)
    {
        return $this->callAPI(
            "modif-document.php",
            array('id_e' => $this->pastellProperties->id_e,'id_d' => $id_d),
            array($field => array($file_path,$file_orig_name))
        );
    }

    /**
     * @param $id_d
     * @param $actes_file_path
     * @param $actes_orig_file_name
     * @return bool|mixed
     * @throws Exception
     */
    public function postActes($id_d, $actes_file_path, $actes_orig_file_name)
    {
        return $this->postFile($id_d, 'arrete', $actes_file_path, $actes_orig_file_name);
    }

    /**
     * @param $id_d
     * @param $annexe_path
     * @param $annexe_orig_name
     * @return bool|mixed
     * @throws Exception
     */
    public function postAnnexe($id_d, $annexe_path, $annexe_orig_name)
    {
        return $this->postFile($id_d, 'autre_document_attache', $annexe_path, $annexe_orig_name);
    }

    /**
     * @param $id_d
     * @param $ar_acte_path
     * @return bool|mixed
     * @throws Exception
     */
    public function postARActes($id_d, $ar_acte_path)
    {
        return $this->postFile($id_d, 'aractes', $ar_acte_path);
    }

    /**
     * @param $id_d
     * @param $echange_prefecture_type
     * @param $echange_prefecture
     * @param $echange_prefecture_ar
     * @return bool|mixed
     * @throws Exception
     */
    public function postRelatedTransaction($id_d, $echange_prefecture_type, $echange_prefecture, $echange_prefecture_ar)
    {
        foreach ($echange_prefecture as $ep) {
            $this->postFile($id_d, 'echange_prefecture', $ep[0], $ep[1]);
        }
        foreach ($echange_prefecture_ar as $ep) {
            $this->postFile($id_d, 'echange_prefecture_ar', $ep[0], $ep[1]);
        }
        $info = array('id_e' => $this->pastellProperties->id_e,'id_d' => $id_d);
        foreach ($echange_prefecture_type as $i => $type) {
            $info['echange_prefecture_type_' . $i] = $type;
        }
        return $this->callAPI("modif-document.php", $info);
    }

    /**
     * @param string $id_d
     * @param string $field
     * @param array $metadata
     * @return bool|mixed
     * @throws Exception
     */
    public function modifExternalData(string $id_d, string $field, array $metadata)
    {
        return $this->callAPI(
            sprintf(
                "/v2/Entite/%s/document/%s/externalData/%s",
                $this->pastellProperties->id_e,
                $id_d,
                $field
            ),
            $metadata,
            [],
            "PATCH"
        );
    }

    /**
     * @param $id_d
     * @return bool|mixed
     * @throws Exception
     */
    public function sendSAE($id_d, $action = 'send-archive')
    {
        $info = array('id_e' => $this->pastellProperties->id_e,'id_d' => $id_d,'action' => $action);
        return $this->callAPI("action.php", $info);
    }

    /**
     * @param $id_d
     * @return bool|mixed
     * @throws Exception
     */
    public function getInfo($id_d)
    {
        $info = array('id_e' => $this->pastellProperties->id_e,'id_d' => $id_d);
        return $this->callAPI("detail-document.php", $info);
    }

    /**
     * @param $id_d
     * @param $field
     * @return bool|mixed
     * @throws Exception
     */
    public function getFile($id_d, $field)
    {
        $url = "recuperation-fichier.php?id_e={$this->pastellProperties->id_e}&id_d=$id_d&field=$field";
        $curl_wrapper = $this->curlWrapperFactory->getNewInstance();
        $curl_wrapper->dontVerifySSLCACert();
        $curl_wrapper->httpAuthentication($this->pastellProperties->login, $this->pastellProperties->password);
        $curl_wrapper->setTimeout(300, 3600);
        $result = $curl_wrapper->get($this->pastellProperties->url . "/" . $url);
        if (! $result) {
            throw new Exception($curl_wrapper->getLastError());
        }
        return $result;
    }

    /**
     * @param $id_d
     * @return bool|mixed
     * @throws Exception
     */
    public function delete($id_d)
    {
        $info = array('id_e' => $this->pastellProperties->id_e,'id_d' => $id_d,'action' => 'supression');
        return $this->callAPI("action.php", $info);
    }

    /**
     * @param $flux
     * @return mixed
     * @throws Exception
     */
    public function listDocuments($flux, $etat = 'verif-sae-erreur')
    {
        $info = array(
            'id_e' => $this->pastellProperties->id_e,
            'type' => $flux,
            'lastetat' => $etat,
            'limit' => 50000
        );

        $output = $this->callAPI("recherche-document.php", $info);
        return $output;
    }

    /**
     * @param $id_d
     * @return bool|mixed
     * @throws Exception
     */
    public function verifSAE($id_d)
    {
        $info = array('id_e' => $this->pastellProperties->id_e,'id_d' => $id_d,'action' => 'verif-sae');
        return $this->callAPI("action.php", $info);
    }
}
