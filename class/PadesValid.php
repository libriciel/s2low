<?php

namespace S2lowLegacy\Class;

use Exception;
use S2low\DTO\PadesValidationResult;
use S2low\Exceptions\PadesValidConnectionException;

class PadesValid
{
    private $pades_valid_url;


    /** @var  VerifyPadesSignature */
    private $verifyPadesSignature;

    private $last_result;

    /** @var CurlWrapperFactory */
    private $curlWrapperFactory;

    public function __construct(
        $pades_valid_url,
        CurlWrapperFactory $curlWrapperFactory,
        VerifyPadesSignature $verifyPadesSignature
    ) {
        $this->pades_valid_url = $pades_valid_url;
        $this->curlWrapperFactory = $curlWrapperFactory;
        $this->verifyPadesSignature = $verifyPadesSignature;
    }

    public function getLastResult()
    {
        return $this->last_result;
    }

    /**
     * @param string $filepath
     * @param string $certificatePath
     * @return \S2low\DTO\PadesValidationResult
     * @throws \Exception
     */
    public function validate(string $filepath, string $certificatePath): PadesValidationResult
    {
        try {
            $result = $this->getPadesValidResult($filepath);
            if ($result === false) {
                return new PadesValidationResult(
                    false,
                    true,
                    false,
                    'Pas de signature',
                    $this->getLastResult()
                );
            }
            foreach ($result->signatures as $signature) {
                $this->verifyPadesSignature->validateSignature($signature, $certificatePath);
            }
            return new PadesValidationResult(
                true,
                true,
                false,
                'Signature valide',
                $this->getLastResult()
            );
        } catch (PadesValidConnectionException $e) {
            return new PadesValidationResult(
                true,
                false,
                true,
                'Erreur de connection à pades-valid : ' . $e->getMessage(),
                $this->getLastResult()
            );
        } catch (Exception $e) {
            return new PadesValidationResult(
                true,
                false,
                false,
                'Erreur lors de la validation PADES : ' . $e->getMessage(),
                $this->getLastResult(),
                $e
            );
        }
    }

    /**
     * @param $filepath
     * @return bool|mixed
     * @throws Exception
     * @throws \S2low\Exceptions\PadesValidConnectionException
     */
    private function getPadesValidResult($filepath)
    {
        $curlWrapper = $this->curlWrapperFactory->getNewInstance();

        $curlWrapper->addPostFile('file', $filepath);
        $result = $curlWrapper->get($this->pades_valid_url);
        $this->last_result = $result;
        if (!$result) {
            if ($curlWrapper->getLastHttpCode()) {
                throw new Exception($curlWrapper->getLastError() . " " . $curlWrapper->getLastOutput());
            }

            throw new PadesValidConnectionException($curlWrapper->getLastError() . " " . $curlWrapper->getLastOutput());
        }
        $result = json_decode($result);
        if (! $result) {
            throw new Exception("Impossible de décoder le message de pades-valid : " . $curlWrapper->getLastOutput());
        }
        if (! isset($result->signed)) {
            throw new Exception("Impossible de determiner si le fichier est signé");
        }

        if ($result->signed == false) {
            //Le fichier n'est pas signée
            return false;
        }
        if (empty($result->signatures)) {
            throw new Exception("Impossible de determiner si le fichier est signé");
        }
        return $result;
    }
}
