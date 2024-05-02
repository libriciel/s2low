<?php

namespace S2lowLegacy\Class;

use CURLFile;
use Exception;

class PDFStampWrapper
{
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

        $data = array(
            'stamps' =>
                array( array(
                    'page' => 0,
            'opacity' => 0.8,
            'fontSize' => 7,
            'position' => array(
                'width' => 190,
                'height' => 55,
                'x' => 10,
                'y' => 10
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
                    'value' => $date_affichage/*,
                    'logo' => array(
                        'imageRef' => 'test',
                        'width' =>  60,
                        'marginRight' =>  20
                    )*/
                ),
                array(
                    'title' => 'ID :',
                    'value' => $pdfStampData->identifiant_unique
                ),
                    ))
        ));

        /* curl -F "file=@Courrier.pdf" -F "metadata=$SAMPLE" -X POST http://pdf-stamp:8080 (!) */
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curlHandle, CURLOPT_MAXREDIRS, 5);

        $file = new CURLFile($pdf_filepath, 'application/pdf', basename($pdf_filepath));
        $stringFile = new \CURLStringFile(json_encode($data), 'metadata', 'application/json');
        /*$images = array('logoRef' => base64_encode(file_get_contents($this->image_for_stamp)));
        $imagesFile = new \CURLStringFile(json_encode($images), 'images', 'application/json');*/
        $image = new CURLFile(__DIR__ . '/../public.ssl/custom/images/s2low-stamp.png', 'image/png', 'test');
        //$data = array('pdfSource' => $file,'stampRequest' => $stringFile/*,'images' => $imagesFile*/);
        $data = array('pdfSource' => $file,'stampRequest' => $stringFile,'images' => $image);

        curl_setopt($curlHandle, CURLOPT_POST, true); // enable posting
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $data); // post images

        curl_setopt($curlHandle, CURLOPT_URL, 'http://pdf-stamp:8889/pdf-stamp/v3/stamp/add');
        $last_output = curl_exec($curlHandle);

        //print_r(curl_getinfo($this->curlHandle,CURLINFO_HEADER_OUT));
        //echo $url;
        $httpcode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        //var_dump($last_output);
        //var_dump($httpcode);
        //die();
        //var_dump($last_output);
        //var_dump(curl_error($curlHandle));
        //die();
        return $last_output;
    }

    private function getDateFr($date)
    {
        if (is_null($date)) {
            return date('d/m/Y');
        }
        return date('d/m/Y', strtotime($date));
    }
}
