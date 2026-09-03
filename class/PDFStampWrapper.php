<?php

namespace S2lowLegacy\Class;

use CURLStringFile;
use Exception;

class PDFStampWrapper
{
    private const STAMP_ADD_PATH = '/pdf-stamp/v3/stamp/add';
    private const LOGO_IMAGE_REF = 's2low-stamp.png';

    private $pdf_stamp_url;
    private $image_for_stamp;

    /** @var CurlWrapperFactory */
    private $curlWrapperFactory;
    /**
     * @var \S2lowLegacy\Class\PdfStampMessages
     */
    private PdfStampMessages $pdfStampMessage;

    public function __construct($pdf_stamp_url, $image_for_stamp, PdfStampMessages $pdfStampMessages)
    {
        $this->pdf_stamp_url = $pdf_stamp_url;
        $this->image_for_stamp = $image_for_stamp;
        $this->setCurlWrapperFactory(new CurlWrapperFactory());
        $this->pdfStampMessage = $pdfStampMessages;
    }

    public function setCurlWrapperFactory(CurlWrapperFactory $curlWrapperFactory)
    {
        $this->curlWrapperFactory = $curlWrapperFactory;
    }

    /**
     * @param $pdf_filepath
     * @param PDFStampData $pdfStampData
     * @return bool|mixed
     * @throws Exception
     */
    public function stamp($pdf_filepath, PDFStampData $pdfStampData)
    {
        $date_affichage = "";
        if ($pdfStampData->affichage_date) {
            $date_affichage = $this->getDateFr($pdfStampData->affichage_date);
        }

        $stampRequest = array(
            'stampList' => array(
                array(
                    'page' => 1,
                    'opacity' => 0.8,
                    'fontSize' => 7,
                    'position' => array(
                        'width' => 190,
                        'height' => 55,
                        'x' => 10,
                        'y' => 10,
                        // origine implicite de l'API v2, à expliciter en v3 pour conserver le même rendu
                        'origin' => 'TOP_RIGHT'
                    ),
                    'rows' => array(
                        array(
                            'title' => $this->pdfStampMessage->getMessageEnvoi(),
                            'value' => $this->getDateFr($pdfStampData->envoi_prefecture_date),
                        ),
                        array(
                            'title' => $this->pdfStampMessage->getMessageReception(),
                            'value' => $this->getDateFr($pdfStampData->recu_prefecture_date)
                        ),
                        array(
                            'title' => $this->pdfStampMessage->getMessagePublication(),
                            'value' => $date_affichage,
                            'logo' => array(
                                'imageRef' => self::LOGO_IMAGE_REF,
                                "width" =>  60,
                                "marginRight" =>  20
                            )
                        ),
                        array(
                            'title' => 'ID :',
                            'value' => $pdfStampData->identifiant_unique
                        ),
                    )
                )
            )
        );

        /* curl -F "pdfSource=@Courrier.pdf" -F "images=@s2low-stamp.png" \
               -F "stampRequest=$SAMPLE;type=application/json" \
               -X POST http://pdf-stamp:8080/pdf-stamp/v3/stamp/add */
        $curlWrapper = $this->curlWrapperFactory->getNewInstance();
        $curlWrapper->addPostFile('pdfSource', $pdf_filepath);
        // le nom du fichier sert de référence à la propriété imageRef du logo
        $curlWrapper->addPostFile('images', $this->image_for_stamp, self::LOGO_IMAGE_REF);
        // pdf-stamp répond 415 si la partie stampRequest n'est pas déclarée en application/json.
        // Un CURLStringFile permet de typer la partie sans passer par un fichier temporaire.
        $curlWrapper->addPostData(
            'stampRequest',
            new CURLStringFile(json_encode($stampRequest), 'stampRequest.json', 'application/json')
        );

        $result = $curlWrapper->get($this->getStampAddUrl());
        if (!$result) {
            throw new Exception($curlWrapper->getLastError() . " " . $curlWrapper->getLastOutput());
        }
        return $result;
    }

    private function getStampAddUrl(): string
    {
        return rtrim($this->pdf_stamp_url, '/') . self::STAMP_ADD_PATH;
    }

    private function getDateFr($date)
    {
        if (is_null($date)) {
            return date('d/m/Y');
        }
        return date('d/m/Y', strtotime($date));
    }
}
