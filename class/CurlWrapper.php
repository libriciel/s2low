<?php

namespace S2lowLegacy\Class;

use CURLFile;
use Exception;

class CurlWrapper
{
    private const POST_DATA_SEPARATOR = "\r\n";

    private $curlHandle;
    private $lastError;
    private $postData;
    private $postFile;
    private $postFileProperties;
    private $last_output;
    private $lastHttpCode;

    private $patch;

    public function __construct()
    {
        $this->curlHandle = curl_init();
        $this->setProperties(CURLOPT_RETURNTRANSFER, 1);
        $this->setProperties(CURLOPT_FOLLOWLOCATION, 1);
        $this->setProperties(CURLOPT_MAXREDIRS, 5);
        $this->postFile = array();
        $this->postData = array();
    }

    public function __destruct()
    {
        curl_close($this->curlHandle);
    }

    public function httpAuthentication($username, $password)
    {
        $this->setProperties(CURLOPT_USERPWD, "$username:$password");
        $this->setProperties(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function getLastOutput()
    {
        return $this->last_output;
    }

    public function getLastHttpCode()
    {
        return $this->lastHttpCode;
    }

    public function setProperties($properties, $values)
    {
        curl_setopt($this->curlHandle, $properties, $values);
    }

    public function setAccept($format)
    {
        $curlHttpHeader[] = "Accept: $format";
        $this->setProperties(CURLOPT_HTTPHEADER, $curlHttpHeader);
    }

    public function dontVerifySSLCACert()
    {
        $this->setProperties(CURLOPT_SSL_VERIFYHOST, 0);
        $this->setProperties(CURLOPT_SSL_VERIFYPEER, 0);
    }

    public function setServerCertificate($serverCertificate)
    {
        $this->setProperties(CURLOPT_CAINFO, $serverCertificate);
        $this->setProperties(CURLOPT_SSL_VERIFYPEER, 0);
    }

    public function setClientCertificate($clientCertificate, $clientKey, $clientKeyPassword)
    {
        $this->setProperties(CURLOPT_SSLCERT, $clientCertificate);
        $this->setProperties(CURLOPT_SSLKEY, $clientKey);
        $this->setProperties(CURLOPT_SSLKEYPASSWD, $clientKeyPassword);
    }

    public function setHttpsConnexionWithVerifPeerPubKey($pubKeyHash): void
    {
        $this->setProperties(CURLOPT_SSL_VERIFYHOST, 0);
        $this->setProperties(CURLOPT_SSL_VERIFYPEER, false);
        $this->setProperties(CURLOPT_CERTINFO, 1);
        $this->setProperties(CURLOPT_PINNEDPUBLICKEY, "sha256//" . $pubKeyHash);
    }

    public function setPatch()
    {
        $this->patch = true;
        $this->setProperties(CURLOPT_CUSTOMREQUEST, "PATCH");
    }

    public function setTimeout(int $connectTimeout, int $timeout)
    {
        $this->setProperties(CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        $this->setProperties(CURLOPT_TIMEOUT, $timeout); //timeout in seconds
    }

    public function get($url)
    {
        $this->setProperties(CURLOPT_URL, $url);
        if ($this->postData || $this->postFile) {
            $this->curlSetPostData();
        }
        //curl_setopt($this->curlHandle, CURLINFO_HEADER_OUT, true);

        $this->last_output = curl_exec($this->curlHandle);

        //print_r(curl_getinfo($this->curlHandle,CURLINFO_HEADER_OUT));
        //echo $url;
        $httpcode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
        $this->lastHttpCode = $httpcode;
        if (! in_array($httpcode, array('200','201','0'))) {
            $this->lastError = "Erreur HTTP : Code $httpcode";
            return false;
        }

        $this->lastError = curl_error($this->curlHandle);
        if ($this->lastError) {
            $this->lastError = "Erreur de connexion au serveur : " . $this->lastError;

            return false;
        }


        return $this->last_output;
    }

    public function addPostData($name, $value)
    {
        if (! isset($this->postData[$name])) {
            $this->postData[$name] = array();
        }

        $this->postData[$name][] = $value;
    }

    public function addPostFile($field, $filePath, $fileName = false, $contentType = "application/octet-stream", $contentTransferEncoding = false)
    {
        if (! $fileName) {
            $fileName = basename($filePath);
        }
        $this->postFile[$field][$fileName] = $filePath;
        $this->postFileProperties[$field][$fileName] = array($contentType,$contentTransferEncoding);
    }

    private function getBoundary()
    {
        return '----------------------------' .
            mb_substr(sha1('CurlWrapper' . microtime()), 0, 12);
    }

    private function curlSetPostData()
    {
        $this->setProperties(CURLOPT_POST, true);
        if ($this->isPostDataWithSimilarName()) {
            $this->curlSetPostDataWithSimilarFilename();
        } else {
            $this->curlPostDataStandard();
        }
    }

    private function isPostDataWithSimilarName()
    {
        $array = array();

        //cURL ne permet pas de poster plusieurs fichiers avec le même nom !
        //cette fonction est inspiré de http://blog.srcmvn.com/multiple-values-for-the-same-key-and-file-upl
        foreach ($this->postData as $name => $multipleValue) {
            foreach ($multipleValue as $data) {
                if (isset($array[$name])) {
                    return true;
                }
                $array[$name] = true;
            }
        }
        foreach ($this->postFile as $name => $multipleValue) {
            foreach ($multipleValue as $data) {
                if (isset($array[$name])) {
                    return true;
                }
                $array[$name] = true;
            }
        }
    }

    private function curlPostDataStandard()
    {
        //print_r($this->postFile);
        $post = array();
        foreach ($this->postData as $name => $multipleValue) {
            foreach ($multipleValue as $value) {
                $post[$name] = $value;
            }
        }
        foreach ($this->postFile as $name => $multipleValue) {
            foreach ($multipleValue as $fileName => $filePath) {
                $post[$name] = new CURLFile($filePath, null, $fileName);
            }
        }
        if ($this->patch) {
            $post = http_build_query($post);
        }

        @ curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $post);
    }

    private function curlSetPostDataWithSimilarFilename()
    {
        //cette fonction, bien que résolvant la limitation du problème de nom multiple de fichier
        //nécessite le chargement en mémoire de l'ensemble des fichiers.
        $boundary = $this->getBoundary();

        $body = array();

        foreach ($this->postData as $name => $multipleValue) {
            foreach ($multipleValue as $value) {
                $body[] = "--$boundary";
                $body[] = "Content-Disposition: form-data; name=$name";
                $body[] = '';
                $body[] = $value;
            }
        }


        foreach ($this->postFile as $name => $multipleValue) {
            foreach ($multipleValue as $fileName => $filePath) {
                $body[] = "--$boundary";
                $body[] = "Content-Disposition: form-data; name=$name; filename=\"$fileName\"";
                $body[] = "Content-Type: {$this->postFileProperties[$name][$fileName][0]}";
                if ($this->postFileProperties[$name][$fileName][1]) {
                    $body[] = "Content-Transfer-Encoding: {$this->postFileProperties[$name][$fileName][1]}";
                }
                $body[] = '';
                $body[] = file_get_contents($filePath);
            }
        }

        $body[] = "--$boundary--";
        $body[] = '';

        $content = join(self::POST_DATA_SEPARATOR, $body);


        $curlHttpHeader[] = 'Content-Length: ' . mb_strlen($content);
        $curlHttpHeader[] = 'Expect: 100-continue';
        $curlHttpHeader[] = "Content-Type: multipart/form-data; boundary=$boundary";

        $this->setProperties(CURLOPT_HTTPHEADER, $curlHttpHeader);
        $this->setProperties(CURLOPT_POSTFIELDS, $content);
    }

    public function getHTTPCode()
    {
        return curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
    }

    public function getInfo()
    {
        return curl_getinfo($this->curlHandle);
    }

    public function getServerCertificate()
    {
        $info = $this->getInfo();
        if (!$info) {
            throw new Exception("Impossible de récupérer les informations sur la connexion Curl");
        }
        if (empty($info['certinfo'])) {
            throw new Exception("Impossible de récupérer le certificat de la connexion Curl");
        }

        return $info['certinfo'][0]['Cert'];
    }
}
