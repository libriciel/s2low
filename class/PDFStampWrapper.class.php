<?php


class PDFStampWrapper {

    private $pdf_stamp_url;
    private $image_for_stamp;

    /** @var CurlWrapperFactory */
    private $curlWrapperFactory;

    public function __construct($pdf_stamp_url, $image_for_stamp) {
        $this->pdf_stamp_url = $pdf_stamp_url;
        $this->image_for_stamp = $image_for_stamp;
        $this->setCurlWrapperFactory(new CurlWrapperFactory());
    }

    public function setCurlWrapperFactory(CurlWrapperFactory $curlWrapperFactory){
        $this->curlWrapperFactory = $curlWrapperFactory;
    }

    /**
     * @param $pdf_filepath
     * @param PDFStampData $pdfStampData
     * @return bool|mixed
     * @throws Exception
     */
    public function stamp($pdf_filepath,PDFStampData $pdfStampData){
        $date_affichage = "";
        if ($pdfStampData->affichage_date){
            $date_affichage = $this->getDateFr($pdfStampData->affichage_date);
        }

        $data = array(
            'opacity'=> 0.8,
            'fontSize' => 7,
            'position' => array(
                'width' => 190,
                'height' => 55,
                'x'=> 10,
                'y'=>10
            ),
            'rows' => array(
                array(
                    'title' => 'Envoyé en préfecture le',
                    'value' => $this->getDateFr($pdfStampData->envoi_prefecture_date),
                ),
                array(
                    'title' => 'Reçu en préfecture le',
                    'value' => $this->getDateFr($pdfStampData->recu_prefecture_date)
                ),
                array(
                    'title' => 'Affiché le',
                    'value' => $date_affichage,
                    'logo' => array(
                        'data' =>  base64_encode(file_get_contents($this->image_for_stamp)),
                        "width" =>  60,
                        "marginRight" =>  30
                    )
                ),
                array(
                    'title' => 'ID :',
                    'value' => $pdfStampData->identifiant_unique
                ),
            )
        );

        /* curl -F "file=@Courrier.pdf" -F "metadata=$SAMPLE" -X POST http://pdf-stamp:8080 (!) */
        $curlWrapper = $this->curlWrapperFactory->getNewInstance();
        $curlWrapper->addPostFile('file',$pdf_filepath);
        $curlWrapper->addPostData('metadata',json_encode(utf8_encode_array($data)));


        $result = $curlWrapper->get($this->pdf_stamp_url);
        if (!$result){
            throw new Exception($curlWrapper->getLastError()." ".$curlWrapper->getLastOutput());
        }
        return $result;
    }

    private function getDateFr($date){
        return date('d/m/Y',strtotime($date));
    }


}
