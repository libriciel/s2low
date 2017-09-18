<?php


class PDFStampWrapper {

    private $pdf_stamp_url;

    /** @var  CurlWrapper */
    private $curlWrapper;

    public function __construct($pdf_stamp_url) {
        $this->pdf_stamp_url = $pdf_stamp_url;
        $this->setCurlWrapper(new CurlWrapper());
    }

    public function setCurlWrapper(CurlWrapper $curlWrapper){
        $this->curlWrapper = $curlWrapper;
    }

    public function getLogoPath(){
        return __DIR__."/../public.ssl/custom/images/s2low-stamp.png";
    }

    public function stamp($pdf_filepath,PDFStampData $pdfStampData){
        $date_affichage = "";
        if ($pdfStampData->affichage_date){
            $date_affichage = $this->getDateFr($pdfStampData->affichage_date);
        }

        $data = array(
            'opacity'=> 0.5,
            'fontSize' => 7,
            'position' => array(
                'width' => 168,
                'height' => 50,
                'x'=> 24,
                'y'=>22
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
                        'data' =>  base64_encode(file_get_contents($this->getLogoPath())),
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

        $this->curlWrapper->addPostFile('file',$pdf_filepath);
        $this->curlWrapper->addPostData('metadata',json_encode(utf8_encode_array($data)));


        $result = $this->curlWrapper->get($this->pdf_stamp_url);
        if (!$result){
            throw new Exception($this->curlWrapper->getLastError()." ".$this->curlWrapper->getLastOutput());
        }
        return $result;
    }

    private function getDateFr($date){
        return date('d/m/Y',strtotime($date));
    }


}